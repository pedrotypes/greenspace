#!/usr/bin/env python
# -*- coding: utf-8 -*-

import threading
import uuid
import logging
from utils import Struct


log = logging.getLogger(__name__)

class Games(object):

    __singleton_lock = threading.Lock()
    __singleton_instance = None

    @classmethod
    def instance(cls):
        if not cls.__singleton_instance:
            with cls.__singleton_lock:
                if not cls.__singleton_instance:
                    cls.__singleton_instance = cls()
        return cls.__singleton_instance


    _games = Struct({})


    def add_player(self, websocket):

        if websocket.id.game_id not in self._games:
            self._games[websocket.id.game_id] = Struct({
                'players': {}
            })

        if websocket.id.player_id not in self._games[websocket.id.game_id].players:
            self._games[websocket.id.game_id].players[websocket.id.player_id] = Struct({})

        # assign an uuid for the connection
        websocket.id.uuid = uuid.uuid4()
        self._games[websocket.id.game_id].players[websocket.id.player_id][websocket.id.uuid] = websocket


    def remove_player(self, websocket):

        del self._games[websocket.id.game_id].players[websocket.id.player_id][websocket.id.uuid]


    def send_message(self, msg, game_id, player_id=None):

        if game_id not in self._games:
            log.warning("Tried to send a message to an unknown game (%d). Ignoring.." % game_id)
            return

        if player_id is not None:
            if player_id not in self._games[game_id].players:
                log.warning("Tried to send a message to an unknown player (%d) in game %d. Ignoring.." % (player_id, game_id))
                return

            for (i,ws) in self._games[game_id].players[player_id].iteritems():
                ws.write_message(msg);
        else:
            for (i,player) in self._games[game_id].players.iteritems():
                for (i,ws) in player.iteritems():
                    ws.write_message(msg);

