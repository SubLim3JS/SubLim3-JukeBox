#!/usr/bin/env python3
# -*- coding: utf-8 -*-

# Phoniebox GPIO Button Configuration
# Custom configuration for SubLim3 JukeBox

buttons = [

    {
        "name": "volume_down_previous",
        "pin": 15,
        "pull_up_down": "UP",
        "tap": "volumedown",
        "hold": "prev",
        "double": "pause",
        "hold_time": 1.0
    },

    {
        "name": "volume_up_next",
        "pin": 5,
        "pull_up_down": "UP",
        "tap": "volumeup",
        "hold": "next",
        "double": "pause",
        "hold_time": 1.0
    }

]

# Optional debounce time
debounce_time = 200
