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
    self.resources = ko.observable();
    self.production = ko.observable();
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

    self.fleetPower = ko.computed(function() {
        var power = 0;
        $.each(self.ownFleets(), function(i, fleet) {
            power = power + parseInt(fleet.power(), 10);
        });

        return power;
    });
    self.totalPower = ko.computed(function() {
        var power = parseInt(self.power(), 10);
        power = isNaN(power) ? 0 : power;

        return power + self.fleetPower();
    });

    self.addPower = function(power) {
        power = parseInt(power, 10);
        self.power(self.power() + power);
    };
    self.removePower = function(power) {
        power = parseInt(power, 10);
        self.power(self.power() - power);
    };
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
    self.power = ko.observable();

    self.garrison = function() {
        var fleetData = [];
        $.each(self.fleets(), function(i, f) {
            var fleet = self.map().getFleet(f);
            self.baseObject.addPower(fleet.power());
            self.map().fleets.remove(fleet);
            fleetData.push('fleet[]='+f);
        });

        $.ajax({
            url: base_url + 'play/commands/stationfleets/' + self.base(),
            type: 'POST',
            dataType: 'json',
            data: fleetData.join('&'),
            error: function() {
                alert('Sorry, unable to garrison fleets at this time');
            }
        });
    };
    self.abort = function() {
        var fleetData = ['destination='+self.base()];
        $.each(self.fleets(), function(i, f) {
            fleetData.push('fleet[]='+f);
            self.map().getFleet(f)
                .base(self.baseObject)
                .origin(null)
                .destination(null)
            ;
        });

        $.ajax({
            url: base_url + 'play/commands/movefleets/' + gameId,
            type: 'POST',
            dataType: 'json',
            data: fleetData.join('&'),
            error: function() {
                alert('Sorry, unable to abort jump at this time');
            }
        });
    };
    self.move = function(destination) {
        self.destination(destination.id);
        var o = self.baseObject;
        var d = self.map().getBase(self.destination());

        var fleetData = ['destination='+destination.id];
        $.each(self.fleets(), function(i, f) {
            fleetData.push('fleet[]='+f);
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

        $.ajax({
            url: base_url + 'play/commands/movefleets/' + gameId,
            type: 'POST',
            dataType: 'json',
            data: fleetData.join('&'),
            error: function() {
                alert('Sorry, unable to move fleets at this time');
            }
        });
    };
    self.create = function() {
        var power = self.power();
        power = power > self.baseObject.power() ? self.baseObject.power() : power;

        $.ajax({
            url: base_url + 'play/commands/createfleet/' + self.base(),
            type: 'POST',
            dataType: 'json',
            data: 'power=' + power,
            success: function(res) {
                // add fleet to the graph
                self.map().loadFleets([res]);

                // adjust base power
                self.baseObject.removePower(power);
                self.power(null);
            },
            error: function() {
                alert('Sorry, unable to create fleet at this time');
            }
        });
    };
}

function MapViewModel() {
    var self = this;
    
    // Own player
    self.player = ko.observable(new Player(myPlayer));
    // Neutral player
    self.neutral = new Player({id: 0, name: 'Neutral', color: '#777'});

    self.status = ko.observable();
    self.bases = ko.observableArray([]);
    self.fleets = ko.observableArray([]);
    self.selectedBase = ko.observable();

    self.goToBase = function(id) {
        self.selectedBase(self.getBase(id));
    };

    self.getBase = function(id) { return self.find(id, self.bases()); };
    self.getFleet = function(id) { return self.find(id, self.fleets()); };
    self.find = function(id, haystack) {
        for (var i=0; i<haystack.length; i++)
            if (haystack[i].id() == id)
                return haystack[i];
    };

    self.loadBases = function(bases) {
        for (var i=0; i<bases.length; i++) {
            var data = bases[i];
            var b = self.getBase(data.id);
            if (b) {
                b
                    .id(data.id)
                    .name(data.name)
                    .power(data.power)
                    .resources(data.resources)
                    .production(data.production)
                ;
            } else {
                var p = data.player ? new Player(data.player) : map.neutral;
                b = new Base(self)
                    .id(data.id)
                    .name(data.name)
                    .power(data.power)
                    .resources(data.resources)
                    .production(data.production)
                    .player(p)
                    .inFleetRange(data.inFleetRange)
                ;
                self.bases.push(b);
            }
        }
    };
    self.loadFleets = function(fleets) {
        for (var i=0; i<fleets.length; i++) {
            var data = fleets[i];
            var f = self.getFleet(data.id);
            var b = self.getBase(data.base);
            var o = self.getBase(data.origin);
            var d = self.getBase(data.destination);

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
                self.fleets.push(new Fleet()
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
$G.state = new MapViewModel();
ko.applyBindings($G.state);