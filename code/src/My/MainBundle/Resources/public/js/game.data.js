/**
 * Handle game data and comands using KnockoutJS
 */

function Status(data) {
    this.bases = ko.observable(data.bases);
    this.production = ko.observable(data.production);
    this.ships = ko.observable(data.ships);
    this.fleets = ko.observable(data.fleets);
}

function StateViewModel() {
    // Data
    var self = this;
    self.status = new Status({});
}

$G.state = new StateViewModel();

ko.applyBindings($G.state);
