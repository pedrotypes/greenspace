#!/usr/bin/env python
# -*- coding: utf-8 -*-

from utils import Struct
import logging
import json

log = logging.getLogger(__name__)


class _ConfigStore(object):
    """Aux class to store the config"""

    config = None

    @classmethod
    def store_config(self, config):
        self.config = config

    @classmethod
    def get_config(self):
        return self.config


def get_config():
    return _ConfigStore.get_config()


def load_config(filename):
    """Parses the configuration file."""

    global config

    def _json_str(item):
        """Helper function to cast JSON unicode data to plain str"""

        if isinstance(item, dict):
            return dict((_json_str(key), _json_str(value)) for key, value in item.iteritems())
        elif isinstance(item, list):
            return [_json_str(element) for element in item]
        elif isinstance(item, unicode):
            return item.encode('utf-8')
        else:
            return item

    try:
        config = Struct(json.load(open(filename, 'r'),object_hook=_json_str))
    except Exception, e:
        log.error('Error loading configuration file %s: %s' % (filename, e))
        raise e

    _ConfigStore.store_config(config)

    return config
