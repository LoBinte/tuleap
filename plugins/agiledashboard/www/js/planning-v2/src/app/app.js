(function () {
    angular.module('planning', [
        'ui.router',
        'ui.tree',
        'templates-app',
        'gettext',
        'angularMoment',
        'ngSanitize',
        'ngAnimate',
        'shared-properties',
        'backlog-item',
        'milestone',
        'project',
        'user-preferences',
        'infinite-scroll',
        'tuleap-artifact-modal',
        'tuleap.artifact-modal'
    ]);
})();
