// Map offsets
var ox = 20;
var oy = 20;
function x(n) { return ox + n; }
function y(n) { return oy + n; }

$G = {
    id: gameId,
    stateUri: base_url + 'play/games/' + gameId + '/state',
    canvas: Raphael(document.getElementById('map-container', 500+ox, 500+oy)),
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

                $G.bases = state.bases;
                if ($G.refreshCount === 0) $G.drawBases();
                
                $G.fleets = state.fleets;
                $G.drawFleets();

                $G.drawBaseOverlays();

                $G.refreshCount++;
            }
        });
    },

    drawBases: function() {
        $.each($G.bases, function(i, data) {
            var base = data.base;
            $G.basesIndex[base.id] = i;

            // Base core
            $G.canvas
                .circle(base.x+ox, base.y+oy, base.resources / 2)
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
                .text(base.x+ox, base.y+oy + 16, base.name)
                .attr({
                    "fill": "#ddd",
                    "font-size": 12
                })
            ;
        });
    },

    drawBaseOverlays: function() {
        $.each($G.bases, function(i, data) {
            var base = data.base;

            // Economy ring
            $G.overlays.push($G.canvas
                .circle(x(base.x), y(base.y), base.resources * 1.5)
                .attr({
                    "stroke": "#444",
                    "fill": "#000",
                    "fill-opacity": 0,
                    "cursor": "pointer"
                })
            );

            // Ownership ring
            var owner_color = '';
            if (base.owned === true) owner_color = "#5f5";
            else if (base.enemy === true) owner_color = "#f00";

            if (base.neutral !== true) {
                $G.overlays.push($G.canvas
                    .circle(x(base.x), y(base.y), 7)
                    .attr({
                        "stroke": owner_color,
                        "stroke-width": 2
                    })
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

    drawFleets: function() {
        $.each($G.fleetIcons, function(i, icon) {
            icon.remove();
        });
        $G.fleetIcons.length = 0;

        $.each($G.fleets, function(i, fleet) {
            // Draw moving fleet path
            if (fleet.isMoving) {
                var origin = $G.getBase(fleet.origin);
                var destination = $G.getBase(fleet.destination.id);
                
                var pathString = "M"
                    + x(origin.base.x) + "," + y(origin.base.y)
                    + "L" + x(destination.base.x) + "," + y(destination.base.y)
                ;

                var path = $G.canvas
                    .path(pathString)
                    .attr({
                        "stroke": "#373",
                        "stroke-width": 2
                    })
                    .toBack()
                ;
                $G.overlays.push(path);
            }

            // Draw the fleet itself
            var icon = $G.canvas
                .circle(x(fleet.coords.x), y(fleet.coords.y), 3)
                .attr({
                    "fill": "#0f0"
                })
            ;

            // Animate the fleet, because awesome
            // It might burn lots and lots and lots of cpu cycles, but hey.
            if (fleet.isMoving) {
                icon.animate({
                    cx: x(fleet.destination.x),
                    cy: y(fleet.destination.y),
                }, parseInt(fleet.destination.timeleft, 10) * 1000);
            }

            $G.overlays.push(icon);
        });
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
            .circle(base.base.x+ox, base.base.y+oy, base.fleetRange)
            .attr({
                "fill": "#fff",
                "fill-opacity": 0.1,
                "stroke-width": 0
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
        var destination = parseInt($(e.target).attr("value"), 10);
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
$("#game-panel").on('click', '.fleet-select-destination', $G.fleetMove);

// Get the ball rolling
$G.refresh();
window.setInterval($G.refresh, $G.refreshInterval);
