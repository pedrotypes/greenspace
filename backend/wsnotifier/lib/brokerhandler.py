#!/usr/bin/env python
# -*- coding: utf-8 -*-

import json
import logging
from greenspace import Games
from utils import Struct

log = logging.getLogger(__name__)

class BrokerHandler(object):

    _game = None
    _broker = None

    def __init__(self, broker):
        self._game = Games.instance()
        self._broker = broker

    # Callback functions below

    def relay_msg(self, message):
        try:
            msg = Struct(json.loads(message.body))

            game_id = int(msg.destination.game_id)
            player_id = None
            if 'player_id' in msg.destination:
                player_id = int(msg.destination.player_id)

            self._game.send_message(msg=msg.message, game_id=game_id, player_id=player_id)

        except ValueError, e:
            # Ack the message anyway, we don't want to keep
            # bad formatted messages coming back
            log.error("%s" % e)
            self._broker.acknowledge(message)
            return False

        self._broker.acknowledge(message)
        return True
