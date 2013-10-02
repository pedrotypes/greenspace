// Map offsets
var ox = 30;
var oy = 20;
function x(n) { return ox + n; }
function y(n) { return oy + n; }

// Handlebars helpers
Handlebars.registerHelper('basename', function(id) {
    return $G.getBase(id).name;
});

// Game object
$G = {
    id: gameId,
    stateUri: base_url + 'play/games/' + gameId + '/state',
    canvas: Raphael(document.getElementById('map-container', 600, 600)),
    zoom: 1,
    refreshInterval: 5000,
    refreshCount: 0,
    bases: {},
    basesIndex: {},
    baseRanges: [],
    fleets: {},
    fleetIcons: [],
    overlays: [],

    refresh: function() {
        $.ajax({
            url: $G.stateUri,
            type: 'GET',
            dataType: 'json',
            async: false,
            success: function(state) {
                $.each($G.overlays, function(i, o) { o.remove(); });
                $G.overlays.length = 0;

                $G.updateStatusBar(state);

                $G.bases = state.bases;
                if ($G.refreshCount === 0) $G.drawBases();
                
                $G.fleets = state.fleets;
                $G.clearBaseInbound();
                $G.drawFleets();

                $G.drawBaseOverlays();

                $G.refreshCount++;
            }
        });
    },

    updateStatusBar: function(state) {
        $(".status-bases").html(state.status.bases);
        $(".status-ships").html(state.status.ships);
        $(".status-fleets").html(state.status.fleets);
        $(".status-production").html(state.status.production);
        $(".map-status-inner-wrapper").show();
    },

    drawBases: function() {
        $.each($G.bases, function(i, base) {
            $G.basesIndex[base.id] = i;

            // Base core
            $G.canvas
                .circle(x(base.x), y(base.y), base.resources / 2)
                .attr({
                    "fill": "#fff"
                })
                .glow({
                    "width": 5,
                    "fill": true,
                    "color": "#ffc",
                    "opacity": 0.2
                })
                // data and click events go to the economy ring,
                // which presents a larger, friendlier click area
            ;

            // Base name
            $G.canvas
                .text(x(base.x), y(base.y) + 16, base.name)
                .attr({
                    "fill": "#ddd",
                    "font-size": 12,
                    "cursor": "pointer"
                })
                .click(function() {
                    $G.selectBase(base.id);
                })
            ;
        });
    },

    drawBaseOverlays: function() {
        $.each($G.bases, function(i, base) {
            if (base.player && base.player.id == playerId) base.owned = true;
            base.totalPower = parseInt(base.power, 10) + parseInt(base.fleetPower, 10);
            if (isNaN(base.totalPower)) base.totalPower = "?";

            // Stats
            if (base.production > 0) { // Knowing the production rate implies detection
                // Power rating and production rate
                $G.overlays.push($G.canvas
                    .text(x(base.x), y(base.y) + 26, base.totalPower + " (" + base.production + ")")
                    .attr({
                        "fill": "#999",
                        "font-size": 10
                    })
                );

                // Production ring
                $G.overlays.push($G.canvas
                    .circle(x(base.x), y(base.y), base.production * 1.5)
                    .attr({
                        "stroke": "#444",
                        "fill": "#000",
                        "fill-opacity": 0
                    })
                    .toBack()
                );
            }

            // Ownership ring
            if (base.player) {
                $G.overlays.push($G.canvas
                    .circle(x(base.x), y(base.y), 7)
                    .attr({
                        "stroke": base.player.color,
                        "stroke-width": 2
                    })
                );
            }

            // Detection ring
            if (base.player) {
                $G.overlays.push($G.canvas
                    .circle(x(base.x), y(base.y), base.detection)
                    .attr({
                        "stroke": base.player.color,
                        "stroke-width": 1,
                        "stroke-dasharray": "- ",
                        "fill": base.player.color,
                        "opacity": 0.075
                    })
                    .toBack()
                );
            }

            // Clickable area
            $G.overlays.push($G.canvas
                .circle(x(base.x), y(base.y), 10)
                .attr({
                    "stroke-width": 0,
                    "fill": "#fff",
                    "fill-opacity": 0,
                    "cursor": "pointer"
                })
                .data("base", base)
                .click(function() {
                    var base = this.data("base");
                    $G.selectBase(base.id);
                })
                .toFront()
            );
        });
    },

    clearBaseInbound: function() {
        $.each($G.bases, function(i, base) {
            if (base.inbound) {
                base.inbound.length = 0;
            }
        });
    },

    drawFleets: function() {
        $.each($G.fleetIcons, function(i, icon) {
            icon.remove();
        });
        $G.fleetIcons.length = 0;

        $.each($G.fleets, function(i, fleet) {
            // Draw moving fleet path
            if (fleet.isMoving) {
                var origin = $G.getBase(fleet.origin);
                var destination = $G.getBase(fleet.destination);

                $G.addInbound(fleet.destination, fleet);
                
                var pathString = "M"
                    + x(origin.x) + "," + y(origin.y)
                    + "L" + x(destination.x) + "," + y(destination.y)
                ;

                var path = $G.canvas
                    .path(pathString)
                    .attr({
                        "stroke": fleet.player.color,
                        "stroke-width": 2
                    })
                    .toBack()
                ;
                $G.overlays.push(path);
            }

            // Report parked fleets
            if (fleet.base) {
                $G.addOrbiting(fleet.base, fleet);
            }

            // Draw the fleet itself
            var icon = $G.canvas
                .circle(x(fleet.coords.x), y(fleet.coords.y), 3)
                .attr({
                    "fill": fleet.player.color
                })
            ;


            $G.overlays.push(icon);
        });
    },

    addInbound: function(baseId, fleet) {
        var base = $G.getBase(baseId);
        if (!base.inbound) base.inbound = [];
        base.inbound.push(fleet);
    },

    addOrbiting: function(baseId, fleet) {
        var base = $G.getBase(baseId);
        if (!base.orbiting) base.orbiting = [];
        base.orbiting.push(fleet);
    },

    basePanelTpl: null,
    drawBasePanel: function(base) {
        if (!$G.basePanelTpl) {
            var source = $("#tpl-base-panel").html();
            var template = Handlebars.compile(source);

            $("#game-panel").html(template(base));
        }
    },

    clearBaseOverlays: function() {
        $.each($G.baseRanges, function(i, e) { e.remove(); });
        $G.baseRanges.length = 0;
    },

    drawFleetRange: function(base) {
        var range = $G.canvas
            .circle(x(base.x), y(base.y), base.jump)
            .attr({
                "fill": "#fff",
                "fill-opacity": 0,
                "stroke": "#eee",
                "stroke-width": 1
            })
            .toBack()
        ;

        $G.baseRanges.push(range);
    },

    getBase: function(baseId) {
        var key = $G.basesIndex[baseId];
        return $G.bases[key];
    },

    selectBase: function(baseId) {
        $G.clearBaseOverlays();

        var base = $G.getBase(baseId);
        $G.drawBasePanel(base);
        $G.drawFleetRange(base);
    },


    // Fleet controls

    getSelectedFleets: function() {
        var fleets = [];
        var checkboxes = $("#game-panel .fleet-check:checked");
        $.each(checkboxes, function(i, c) {
            fleets.push(c.value);
        });

        return fleets;
    },

    handleFleetCheck: function() {
        var checked = $G.getSelectedFleets();
        var fleetcontrol = $(".fleet-control");

        if (checked.length > 0) fleetcontrol.show();
        else fleetcontrol.hide();
    },


    // Commands

    fleetCreate: function(e) {
        var baseId = parseInt($(e.target).attr('data-base'), 10);
        var power = prompt('How many ships?');

        $.ajax({
            url: base_url + 'play/commands/createfleet/' + baseId,
            type: 'POST',
            dataType: 'json',
            data: 'power=' + power,
            success: function(res) {
                $G.refresh();
                $G.selectBase(baseId);
            },
            error: function() {
                alert('Sorry, unable to create fleet at this time');
            }
        });
    },

    fleetStation: function(e) {
        var baseId = parseInt($(e.target).attr('data-base'), 10);
        var fleetIds = $G.getSelectedFleets();
        var fleetData = [];
        $.each(fleetIds, function(i, f) { fleetData.push('fleet[]='+f); });
        fleetData = fleetData.join('&');

        $.ajax({
            url: base_url + 'play/commands/stationfleets/' + baseId,
            type: 'POST',
            dataType: 'json',
            data: fleetData,
            success: function(res) {
                $G.refresh();
                $G.selectBase(baseId);
            },
            error: function() {
                alert('Sorry, unable to station fleets at this time');
            }
        });
    },

    fleetMove: function(e) {
        var baseId = parseInt($(e.target).parent().attr("data-base"), 10);

        var destination = parseInt($(e.target).val(), 10);

        var fleetIds = $G.getSelectedFleets();
        var postData = ['destination='+destination];
        $.each(fleetIds, function(i, f) { postData.push('fleet[]='+f); });
        postData = postData.join('&');

        $.ajax({
            url: base_url + 'play/commands/movefleets/' + gameId,
            type: 'POST',
            dataType: 'json',
            data: postData,
            success: function(res) {
                $G.refresh();
                $G.selectBase(baseId);
            },
            error: function() {
                alert('Sorry, unable to move fleets at this time');
            }
        });
    }
};


// Events
$("#map-refresh").on('click', function() { $G.refresh(); });
$("#game-panel").on('click', '.control-fleet-create', function(e) { $G.fleetCreate(e); });
$("#game-panel").on('change', '.fleet-check', $G.handleFleetCheck);
$("#game-panel").on('click', '.fleet-station', $G.fleetStation);
$("#game-panel").on('change', '.fleet-move', $G.fleetMove);

// Get the ball rolling
$G.refresh();
window.setInterval($G.refresh, $G.refreshInterval);
