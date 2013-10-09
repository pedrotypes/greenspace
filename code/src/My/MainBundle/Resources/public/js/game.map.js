// Map offsets
var ox = 50;
var oy = 50;
function x(n) { return ox + n; }
function y(n) { return oy + n; }
var map_w = canvasW;
var map_h = canvasH;

// Game object
$G = {
    id: gameId,
    stateUri: base_url + 'play/games/' + gameId + '/state',
    container: $("#map-container"),
    canvas: Raphael(document.getElementById('map-container', map_w, map_h)),
    viewBox: {
        x: 0,
        y: 0,
        w: map_w,
        h: map_h
    },
    state: null,
    refreshInterval: 10000,
    refreshCount: 0,
    bases: {},
    basesIndex: {},
    baseRanges: [],
    overlays: [],
    detection: [],

    refresh: function() {
        $.ajax({
            url: $G.stateUri,
            type: 'GET',
            dataType: 'json',
            async: false,
            success: function(state) {
                $.each($G.overlays, function(i, o) { o.remove(); });
                $G.overlays.length = 0;

                $G.state.status(state.status);
                $G.state.loadBases(state.bases);

                $G.bases = state.bases;
                if ($G.refreshCount === 0) $G.drawBases();

                // There's a bug where f.id() returns a string for newly moved fleets, hence the parseInt
                var oldFleets = $.map($G.state.fleets(), function(f) { return parseInt(f.id(), 10); });

                $G.state.loadFleets(state.fleets);
                if ($G.refreshCount > 0) {
                    var serverFleets = $.map(state.fleets, function(f) { return f.id; });
                    $.each(oldFleets, function(i, f) {
                        if ($.inArray(f, serverFleets) == -1) {
                            $G.state.getFleet(f).remove();
                        }
                    });
                }

                $G.drawBaseOverlays();
                $G.refreshCount++;
            }
        });
    },

    setViewBox: function() {
        $G.canvas.setViewBox($G.viewBox.x, $G.viewBox.y, $G.viewBox.w, $G.viewBox.h, true);
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
                        "stroke-width": 2,
                        "stroke-opacity": 0.1,
                        "fill": base.player.color,
                        "fill-opacity": 0.075
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

    drawDetectionRanges: function() {
        $.each($G.detection, function(i, d) { d.remove(); });
        $G.detection.length = 0;

        var players = {};
        $.each($G.bases, function(i, b) {
            if (b.player.id) {
                if (!players[b.player.id]) players[b.player.id] = {player: b.player, bases: []};
                players[b.player.id].bases.push(b);
            }
        });

        $.each(players, function(i, p) {
            if (p.bases.length === 0) return;

            var detection = $G.canvas.set();

            $.each(p.bases, function(i, b) {
                var range = $G.canvas.circle(x(b.x), y(b.y), b.detection);
                detection.push(range);
            });

            var detection_path = $G.canvas.toPath(detection.items[0]);
            for (i=1; i<detection.length; i++) {
                var aux = $G.canvas.path(detection_path);
                detection_path = $G.canvas.union(aux, detection.items[p.bases.length - i]);
                aux.remove();
            }

            detection.remove();

            var newPath = $G.canvas.path(detection_path);
            newPath.attr({
                "stroke": p.player.color,
                "stroke-width": 3,
                "stroke-opacity": 0.1,
                "fill": p.player.color,
                "fill-opacity": 0.1
            }).toBack();

            $G.detection.push(newPath);
        });
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
        $G.drawFleetRange(base);

        $G.state.goToBase(baseId);
    }
};


// Aux functions

function resizeMapView(e, el, step) {
    e.preventDefault();

    // mapCenter(e.pageX - el.offsetLeft, e.pageY - el.offsetTop);
    mapResize(step);
    $G.setViewBox();

    return false;
}

// Center canvas on click location
function mapCenter(x, y) {
    $G.viewBox.x += x - $G.viewBox.w/2;
    $G.viewBox.y += y - $G.viewBox.h/2;
}

// Pan the map by a few pixels
function mapPan(x, y) {
    $G.viewBox.x += x;
    $G.viewBox.y += y;
}

// Resize the map, keeping the current center
function mapResize(step) {
    $G.viewBox.x = $G.viewBox.x - step/2;
    $G.viewBox.y = $G.viewBox.y - step/2;
    $G.viewBox.w += step;
    $G.viewBox.h += step;
}

// Drag the map around
// TODO: Fix the point that was clicked and move IT around
// rather than just accelerating the map in a direction
var isDragging = false;
$("#map-container")
    .mousedown(function(e) {
        var lastPosition = {
            x: e.pageX - $G.container.offset().left,
            y: e.pageY - $G.container.offset().top
        };

        $(window).mousemove(function(e) {
            isDragging = true;

            var pan = {
                x: e.pageX - $G.container.offset().left - lastPosition.x,
                y: e.pageY - $G.container.offset().top - lastPosition.y
            };

            mapPan(pan.x * -1, pan.y * -1);
            $G.setViewBox();

            lastPosition = {
                x: e.pageX - $G.container.offset().left,
                y: e.pageY - $G.container.offset().top
            };
        });
    })
    .mouseup(function() {
        $(window).unbind("mousemove");
        isDragging = !isDragging;
    })
;



// Events

// Double click to zoom in on the map
$("#map-container").on('dblclick', function(e) {
    return resizeMapView(e, this, -100);
});
// Right click to zoom out on the map
$("#map-container").on('mousedown', function(e) {
    if (e.which == 3) return resizeMapView(e, this, 100);
});
$("#map-container").on('contextmenu', function(e) { return false; });

// Window resize
$(window).resize(function() {
    var dimensions = {
        width: $(window).width() + 'px',
        height: $(window).height() - 30 + 'px'
    };

    $("html").attr(dimensions);
    $("#map-container").attr(dimensions);
    $("#map-container svg").attr(dimensions);
});
