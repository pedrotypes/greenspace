#!/usr/bin/env python
# -*- coding: utf-8 -*-

import os, sys

# Make sure our bundled libraries take precedence
sys.path.insert(0,os.path.join(os.path.dirname(os.path.abspath(__file__)),'lib'))

import tornado.ioloop
import tornado.websocket
import uuid
import json
import time
from operator import itemgetter

from tornado.options import define, options, parse_command_line

define("port", default=8888, help="run on the given port", type=int)

# we gonna store clients in dictionary..
clients = dict()
# store game on a dict
games = dict()

# Dummy data
games[2] = {
    'msgs': [
        {'timestamp':1, 'command':'print', 'type': 'production', 'message': 'You gotz some new sheeeps!'},
        {'timestamp':100, 'command':'print', 'type': 'production', 'message': 'You gotz some new sheeeps!'}
    ],
    'players': {
        'cl1': {
            'clients': {},
            'msgs': [
                {'timestamp':8, 'command':'print', 'type': 'combat', 'subtype':'lose', 'message': 'You lost your combat on <em>Namek</em>'}
            ]
        },
        'cl2': {
            'clients': {},
            'msgs': [
                {'timestamp':8, 'command':'print', 'type': 'combat', 'subtype':'win', 'message': 'You won your combat on <em>Namek</em>'}
            ]
        }
    }
}

class WSHandler(tornado.websocket.WebSocketHandler):

    commands = {
        'whoami': '_identify'
    }

    def open(self):
        print 'Connection opened.'
        self.id = uuid.uuid4()
        self.stream.set_nodelay(True)
        clients[self.id] = {"id": self.id, "ws": self}
        self.write_message(json.dumps({'command': 'identify'}))

    def on_message(self, message):

        # Test if is json
        try:
            msg = json.loads(message);
            if not isinstance(msg, dict) or 'command' not in msg:
                raise ValueError('invalid message')

            if msg['command'] in self.commands:
                getattr(self, self.commands[msg['command']])(msg)

            print "Client %s sent a msg : %r" % (self.id, msg)
        except ValueError:
            #print "Client %s sent a message : %s" % (self.id, message)

            # Add the message to the game (assuming game 2)
            msg = {
                'timestamp': time.time(),
                'command':'print',
                'type': 'info',
                'message': message
            }
            games[2]['msgs'].append(msg)

            for key,player in games[2]['players'].iteritems():
                for key,client in player['clients'].iteritems():
                    client['ws'].write_message(json.dumps(msg))

    def on_close(self):
        if self.id in clients:
            if 'g_id' in clients[self.id]:
                game = clients[self.id]['g_id']['game']
                player = clients[self.id]['g_id']['player']
                print 'Client (%s) [Game: %s, Player: %s] closed.' % (self.id, game, player)
                del games[game]['players'][player]['clients'][self.id]
            else:
                print 'Client (%s) closed.' % self.id
            del clients[self.id]

    def _identify(self, msg):
        if msg['player']['game_id'] in games and msg['player']['player_id'] in games[msg['player']['game_id']]['players']:
            player = msg['player']['player_id']
            game = msg['player']['game_id']

            # Add the player to the game players
            clients[self.id]['g_id'] = {'game': game, 'player': player}
            games[game]['players'][player]['clients'][self.id] = clients[self.id]

            # Send all the previous messages to him
            msgs = games[game]['msgs'] + games[game]['players'][player]['msgs']
            msgs = sorted(msgs, key=itemgetter('timestamp'))
            self.write_message(json.dumps({
                'command': 'multiprint',
                'msgs': msgs
            }))



app = tornado.web.Application([
    (r'/ws', WSHandler),
])

if __name__ == '__main__':
    parse_command_line()
    app.listen(options.port)
    tornado.ioloop.IOLoop.instance().start()
