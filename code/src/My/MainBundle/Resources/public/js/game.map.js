$G = {
    id: gameId,
    stateUri: base_url + 'play/games/' + gameId + '/state',
    canvas: Raphael(document.getElementById('map-container', 500, 500)),
    zoom: 1,
    bases: {},
    fleets: {},

    refresh: function() {
        $.getJSON($G.stateUri, function(bases) {
            $G.canvas.clear();
            $G.bases = bases;
            $G.drawBases();
        });
    },

    drawBases: function() {
        for (var i in $G.bases) {
            var base = $G.bases[i];

            // Base core
            $G.canvas
                .circle(base.x, base.y, base.resources / 2)
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

            // Economy ring
            $G.canvas
                .circle(base.x, base.y, base.resources * 1.5)
                .attr({
                    "stroke": "#444",
                    "fill": "#000",
                    "fill-opacity": 0,
                    "cursor": "pointer"
                })
                .data("base", base)
                .click(function() {
                    var base = this.data("base");
                    console.log("Clicked on base #" + base.id + " (x: "+base.x+", y:"+base.y+")")
                })
            ;

            // Ownership ring
            var owner_color = '';
            if (base.owned === true) owner_color = "#5f5";
            else if (base.enemy === true) owner_color = "#f00";

            if (base.neutral !== true) {
                $G.canvas
                    .circle(base.x, base.y, 7)
                    .attr({
                        "stroke": owner_color,
                        "stroke-width": 2
                    })
                ;
            }
        }
    }
};

$G.refresh();
$("#map-refresh").on('click', function() { $G.refresh(); });