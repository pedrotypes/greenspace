#!/usr/bin/env python
# -*- coding: utf-8 -*-

import os, sys

# Make sure our bundled libraries take precedence
sys.path.insert(0,os.path.join(os.path.dirname(os.path.abspath(__file__)),'lib'))

import tornado.ioloop, tornado.websocket
import logging
from config import load_config
from broker import Broker
from brokerhandler import BrokerHandler
from greenspace import WSHandler

log = logging.getLogger(__name__)

def _setup_logging():
    log_level = logging.DEBUG
    log_format = '%(asctime)s %(levelname)-8s %(name)s: %(message)s'
    logging.basicConfig(format=log_format, level=log_level)
    logging.getLogger('pika').setLevel(logging.INFO)


if __name__ == '__main__':

    _setup_logging()

    # Load Config
    config = load_config('etc/config.json')

    # Setup broker
    options = {}
    for param in ['host', 'port', 'username', 'password']:
        if param in config.broker:
            options[param] = config.broker[param]
    broker = Broker(**options)

    # Setup broker handler
    handler = BrokerHandler(broker)

    # Add queues and callbacks
    broker.add_callback(queue=config.broker.queue, callback=handler.relay_msg)

    # Start the tornado web application
    app = tornado.web.Application([
        (r'/ws', WSHandler),
    ])
    app.listen(port=config.server.port, address=config.server.bind_address)

    log.info("Starting the server (ws://%s:%d/ws) IOLoop..." % (config.server.bind_address, config.server.port))
    tornado.ioloop.IOLoop.instance().start()
