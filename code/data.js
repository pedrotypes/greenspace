// Bindings

ko.bindingHandlers.playerColor = {
    update: function(element, valueAccessor) {
        var player = valueAccessor();
        $(element).css({
            color: player.color()
        });
    }
};


// Models

function GameStatus(data) {
    this.bases = ko.observable(data.bases);
    this.ships = ko.observable(data.ships);
    this.fleets = ko.observable(data.fleets);
    this.production = ko.observable(data.production);
}

function Player(data) {
    this.id = ko.observable(data.id);
    this.name = ko.observable(data.name);
    this.color = ko.observable(data.color);
}

function Base(map) {
    var self = this;

    self.id = ko.observable();
    self.player = ko.observable();
    self.map = ko.observable(map);
    self.name = ko.observable();
    self.power = ko.observable();
    self.inFleetRange = ko.observable();

    self.fleetCommand = new FleetCommand(self);

    self.fleets = ko.computed(function() {
        return $.map(self.map().fleets(), function(fleet) {
            if (fleet.isAt(self)) return fleet;
        });
    });

    self.ownFleets = ko.computed(function() {
        return $.map(self.fleets(), function(fleet) {
            if (fleet.belongsTo(self.map().player())) return fleet;
        });
    });

    self.otherFleets = ko.computed(function() {
        return $.map(self.fleets(), function(fleet) {
            if (!fleet.belongsTo(self.map().player())) return fleet;
        });
    });

    self.inbound = ko.computed(function() {
        return $.map(self.map().fleets(), function(fleet) {
            if (fleet.isInboundTo(self)) return fleet;
        });
    });
}

function Fleet() {
    this.id = ko.observable();
    this.player = ko.observable();
    this.base = ko.observable();
    this.origin = ko.observable();
    this.destination = ko.observable();
    this.power = ko.observable();

    this.belongsTo = function(player) { return this.player().id() == player.id(); };
    this.isAt = function(base) { return this.base() && this.base().id() == base.id(); };
    this.isInboundTo = function(base) { return this.destination() && this.destination().id() == base.id(); };
}

function FleetCommand(base) {
    var self = this;
    self.baseObject = base;

    self.map = ko.computed(function() { return self.baseObject.map(); });
    self.base = ko.computed(function() { return self.baseObject.id(); });

    self.fleets = ko.observableArray();
    self.destination = ko.observable();

    self.garrison = function() {
        $.each(self.fleets(), function(i, f) {
            self.map().fleets.remove(self.map().getFleet(f));
        });

        // API call here
    };
    self.abort = function() {
        $.each(self.fleets(), function(i, f) {
            self.map().getFleet(f)
                .base(self.baseObject)
                .origin(null)
                .destination(null)
            ;
        });

        // API call here
    };
    self.move = function(destination) {
        self.destination(destination.id);
        var o = self.baseObject;
        var d = self.map().getBase(self.destination());

        $.each(self.fleets(), function(i, f) {
            var fleet = self.map().getFleet(f);
            if (o == d) {
                fleet
                    .origin(null)
                    .destination(null)
                    .base(o)
                ;
            } else {
                fleet
                    .origin(o)
                    .destination(d)
                ;
            }
        });
        self.fleets.removeAll();

        // API call here
    };
}

function MapViewModel() {
    var self = this;
    
    // Own player
    self.player = ko.observable(new Player({
        id: 1,
        name: 'Candeias',
        color: '#f00'
    }));
    // Neutral player
    self.neutral = new Player({id: 0, name: 'Neutral', color: '#777'});

    self.status = ko.observable();
    self.bases = ko.observableArray([]);
    self.fleets = ko.observableArray([]);

    self.getBase = function(id) { return self.find(id, self.bases()); };
    self.getFleet = function(id) { return self.find(id, self.fleets()); };
    self.find = function(id, haystack) {
        for (var i=0; i<haystack.length; i++)
            if (haystack[i].id() == id)
                return haystack[i];
    };

    self.loadBases = function(bases) {
        for (var i=0; i<rawData.bases.length; i++) {
            var data = rawData.bases[i];
            var b = map.getBase(data.id);
            if (b) {
                b
                    .id(data.id)
                    .name(data.name)
                    .power(data.power)
                ;
            } else {
                var p = data.player ? new Player(data.player) : map.neutral;
                b = new Base(map)
                    .id(data.id)
                    .name(data.name)
                    .power(data.power)
                    .player(p)
                    .inFleetRange(data.inFleetRange)
                ;
                map.bases.push(b);
            }
        }
    };
    self.loadFleets = function(fleets) {
        for (var i=0; i<rawData.fleets.length; i++) {
            var data = rawData.fleets[i];
            var f = map.getFleet(data.id);
            var b = map.getBase(data.base);
            var o = map.getBase(data.origin);
            var d = map.getBase(data.destination);

            if (f) {
                f
                    .id(data.id)
                    .base(b)
                    .origin(o)
                    .destination(d)
                    .power(data.power)
                ;
            } else {
                var p = data.player ? new Player(data.player) : map.neutral;
                map.fleets.push(new Fleet()
                    .id(data.id)
                    .base(b)
                    .origin(o)
                    .destination(d)
                    .power(data.power)
                    .player(p)
                );
            }
        }
    };
}


// Get the ball rolling

var map = new MapViewModel();
ko.applyBindings(map);


// Simulate initial state load
var rawPlayers = [
    {id: 0, name: 'Neutral', color: '#777'},
    {id: 1, name: 'Candeias', color: '#f00'},
    {id: 2, name: 'Starman', color: '#00f'}
];
var rawData = {
    status: {
        bases: 34,
        ships: 998,
        fleets: 47,
        production: 501
    },
    bases: [
        {id: 1, name: 'Alpha Centauri', player: rawPlayers[1], power: 15, inFleetRange: [
            {id: 2, name: 'Procyon', distance: 30},
            {id: 3, name: 'Polaris', distance: 90}
        ]},
        {id: 2, name: 'Procyon', player: rawPlayers[2], power: 25, inFleetRange: [
            {id: 3, name: 'Polaris', distance: 80}
        ]},
        {id: 3, name: 'Polaris', player: null, power: 0, inFleetRange: [
            {id: 3, name: 'Polaris', distance: 70}
        ]},
        {id: 4, name: 'Vega', player: null, power: 0, inFleetRange: [
            {id: 3, name: 'Polaris', distance: 60}
        ]},
    ],
    fleets: [
        {id: 1, player: rawPlayers[1], power: 11, base: 1, origin: 1, destination: 2},
        {id: 2, player: rawPlayers[1], power: 22, base: 1},
        {id: 3, player: rawPlayers[1], power: 55, base: 2},
        {id: 4, player: rawPlayers[2], power: 33, base: 2},
        {id: 5, player: rawPlayers[1], power: 44, base: null, origin: 1, destination: 2},
    ]
};

map.status(rawData.status);
// Load bases
map.loadBases(rawData.bases);
// Load fleets
map.loadFleets(rawData.fleets);