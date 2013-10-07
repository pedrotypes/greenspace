/**
 * Handle game data and comands using KnockoutJS
 */

function StateViewModel() {
    // Data
    var self = this;
    self.status = ko.observable();
    self.selectedBase = ko.observable();

    // Behaviours
    self.goToBase = function(data) {
        console.log(data);
        self.selectedBase(data);
    };
    self.clearBase = function() {
        $G.clearBaseOverlays();
        self.selectedBase(null);
    };
}

$G.state = new StateViewModel();

ko.applyBindings($G.state);
