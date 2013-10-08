#!/usr/bin/env python
# -*- coding:utf-8 -*-
# -*- Mode: Python; py-ident-offset: 4 -*-
# vim:ts=4:sw=4:et

import pika
import logging

logger = logging.getLogger(__name__)


class Broker(object):

    class Broker_Callback(object):

        _parent = None
        _queue = None
        _real_callback = None
        _auto_ack = False

        def __init__(self, parent, queue, real_callback, auto_ack=False):
            self._parent = parent
            self._queue = queue
            self._real_callback = real_callback
            self._auto_ack = auto_ack

        def _queue_declared(self, frame):
            logger.debug('Binded queue "%s" (auto_ack=%s)', self._queue, self._auto_ack)
            self._parent._consumer_tags.append(
                self._parent._channel.basic_consume(self._message, queue=self._queue))

        def _message(self, channel, method, header, body):
            logger.debug('Got a new message from queue "%s" (%s): %r', self._queue, method.delivery_tag, body)
            message = Broker.Broker_Message(channel, method, header, body)
            self._real_callback(message)
            if(self._auto_ack is True):
                logger.debug('Auto-ACKing %s', method.delivery_tag)
                self._parent.acknowledge(message)

    # End of Broker Callback

    class Broker_Message(object):

        channel = None
        method = None
        header = None
        body = None
        _acked = False
        _rejected = False

        def __init__(self, channel, method, header, body):
            self.channel = channel
            self.method = method
            self.header = header
            self.body = body

    # End of Broker Message

    _parameters = None
    _connection = None
    _channel = None
    _callbacks = []
    _consumer_tags = []

    def __init__(self, **kargs):

        credentials = {
            'username': 'guest',
            'password': 'guest',
            'erase_on_connect': False
        }
        for key, def_value in credentials.iteritems():
            if key in kargs:
                credentials[key] = kargs[key]
                del kargs[key]
        kargs['credentials'] = pika.PlainCredentials(
            credentials['username'],
            credentials['password'],
            credentials['erase_on_connect']
        )

        self._parameters = pika.ConnectionParameters(**kargs)
        self._connect()

    def _connect(self):
        logger.debug("Connecting to AMQ...")
        self._connection = pika.adapters.TornadoConnection(self._parameters, self._on_connected)

    def _on_connected(self, connection):
        """Called when we are fully connected to RabbitMQ"""
        logger.debug("Connected")

        # Set Unexpected Close Callback
        connection.add_on_close_callback(self._on_unexpected_close)

        # Open a channel
        connection.channel(self._on_channel_open)

    def _on_channel_open(self, new_channel):
        """Called when our channel has opened"""

        logger.debug("Channel Opened")
        self._channel = new_channel

        self._channel.add_on_close_callback(self._on_channel_close)

        # Add Callbacks
        for cb in self._callbacks:
            self._add_callback(cb)

    def _on_unexpected_close(self, method_frame):
        """Called when the connection is closed unexpectedly. Since it is unexpected, we will reconnect"""

        logger.warning('Server closed connection, reopening..')
        self._channel = None
        self._consumer_tags = []
        self._connect()

    def _on_channel_close(self, method_frame):
        """Invoked by pika when RabbitMQ unexpectedly closes the channel.
        Channels are usually closed if you attempt to do something that
        violates the protocol, such as redeclare an exchange or queue with
        different paramters. In this case, we'll close the connection
        to shutdown the object.

        :param pika.frame.Method method_frame: The Channel.Close method frame

        """
        logger.warning('Channel was closed: (%s) %s',
                       method_frame.method.reply_code,
                       method_frame.method.reply_text)
        self._connection.close()

    def add_callback(self, queue, callback, auto_ack=False, durable=True, exclusive=False, auto_delete=False):
        new_entry = {
            'queue': queue,
            'callback': callback,
            'auto_ack': auto_ack,
            'durable': durable,
            'exclusive': exclusive,
            'auto_delete': auto_delete
        }
        self._callbacks.append(new_entry)

        if self._channel is not None:
            self._add_callback(new_entry)

    def _add_callback(self, cb):
        if self._channel is not None:
            logger.debug('Binding queue "%s" (auto_ack=%s)', cb['queue'], cb['auto_ack'])

            auto_ack = cb['auto_ack']
            callback = cb['callback']
            del cb['auto_ack']

            cb['callback'] = self.Broker_Callback(
                self,
                cb['queue'],
                real_callback=callback,
                auto_ack=auto_ack
            )._queue_declared

            self._channel.queue_declare(**cb)

    def acknowledge(self, message):

        if type(message) is Broker.Broker_Message:
            delivery_tag = message.method.delivery_tag
            if message._acked or message._rejected:
                logger.warning(
                    'Ignoring acknowledge request on message %s ' +
                    'because it was already acknowledged or rejected', delivery_tag)
                return False
            else:
                message._acked = True
        else:
            delivery_tag = message

        logger.info("Acknowledging message %s", delivery_tag)
        self._channel.basic_ack(delivery_tag)

        return True

    def reject(self, message, requeue=True):

        if type(message) is Broker.Broker_Message:
            delivery_tag = message.method.delivery_tag
            if message._acked or message._rejected:
                logger.warning(
                    'Ignoring reject request on message %s ' +
                    'because it was already acknowledged or rejected', delivery_tag)
                return False
            else:
                message._rejected = True
        else:
            delivery_tag = message

        logger.info("Rejecting message %s", delivery_tag)
        self._channel.basic_reject(delivery_tag, requeue)

        return True

    def start_loop(self):
        logger.info("Starting Consumer Loop")
        self._connection.ioloop.start()

    def stop_loop(self):
        self._connection.ioloop.stop()

    def _on_cancelok(self, frame):
        ctag = frame.method.consumer_tag
        self._consumer_tags.remove(ctag)

        if len(self._consumer_tags) == 0:
            self._connection.close()

    def stop(self):
        """Cleanly shutdown the connection to RabbitMQ by stopping the consumer
        with RabbitMQ. When RabbitMQ confirms the cancellation, on_cancelok
        will be invoked by pika, which will then closing the channel and
        connection. The IOLoop is started again because this method is invoked
        when CTRL-C is pressed raising a KeyboardInterrupt exception. This
        exception stops the IOLoop which needs to be running for pika to
        communicate with RabbitMQ. All of the commands issued prior to starting
        the IOLoop will be buffered but not processed.

        """
        logger.info('Stopping, and closing connection..')
        for tag in self._consumer_tags:
            self._channel.basic_cancel(self._on_cancelok, tag)
        self._connection.ioloop.start()
