#!/usr/bin/env python
# -*- coding: utf-8 -*-

import tornado.websocket
import json
import logging
import hmac, hashlib
from .games import Games
from config import get_config
from utils import Struct

log = logging.getLogger(__name__)

# Games storage
games = Games.instance()

class WSHandler(tornado.websocket.WebSocketHandler):

    # Accepted client commands
    _commands = {
        'whoami': '_identify'
    }

    def open(self):
        log.debug('New websocket connection oppened. Asking for some ID...')

        # Set nodelay to speed-up sending data
        # (even if doing so would consume additional bandwidth)
        self.stream.set_nodelay(True)

        self.write_message(json.dumps({'command': 'identify'}))


    def on_message(self, message):

        # Test if is json
        try:
            msg = Struct(json.loads(message));
            if not isinstance(msg, dict):
                raise ValueError('Invalid message: Expecting dict, received %s' % type(msg))
            if 'command' not in msg:
                raise ValueError('Invalid message: Doesn\'t have a command entry')

            if msg['command'] in self._commands:
                getattr(self, self._commands[msg['command']])(msg)
            else:
                log.warning("Unknown command received (%s), ignoring..." % msg['command'])

        except ValueError, e:
            log.error("Error while decoding the client message: %s" % e)


    def on_close(self):

        if hasattr(self, 'id'):
            log.debug("Closed connection for player %d, game %d" % (self.id.player_id, self.id.game_id))
            games.remove_player(self);


    def _identify(self, msg):

        # If the client is identified, ignore
        if hasattr(self, 'id'):
            return

        # Test the hmac
        try:
            config = get_config()

            game_id = int(msg.player.game_id)
            player_id = int(msg.player.player_id)
            phmac = str(msg.player.hmac)

            computed_hmac = hmac.new(
                "%d %d" % (game_id, player_id),
                config.security.hmac.key,
                getattr(hashlib, config.security.hmac.digestmod)
            ).hexdigest()

            if phmac != computed_hmac:
                raise ValueError('Invalid HMAC (G: %d, P: %d, H1: %s, H2: %s)' % (game_id, player_id, phmac, computed_hmac))

            self.id = Struct({
                'game_id': game_id,
                'player_id': player_id
            })

        except AttributeError, e:
            log.error("Invalid player id received, missing: %s" % e)
            self.write_message(json.dumps({'command': 'error', 'msg': 'I asked for your identification, and you didn\'t gave it all! Now I shall ignore you...'}))
            return
        except ValueError, e:
            log.error("Invalid player id received: %s" % e)
            self.write_message(json.dumps({'command': 'error', 'msg': 'Sorry, I don\'t know you...'}))
            return

        log.info('New connection from player %d, game %d' % (player_id, game_id))
        games.add_player(self)
