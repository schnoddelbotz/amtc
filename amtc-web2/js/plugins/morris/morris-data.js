$(function() {

    Morris.Area({
        element: 'morris-area-chart',
        data: [{
            period: '2012-02-24 05:45',
            windows: 6,
            linux: null,
            unreachable: 2
        }, {
            period: '2012-02-24 06:00',
            windows: 13,
            linux: 4,
            unreachable: 4
        }, {
            period: '2012-02-24 06:15',
            windows: 20,
            linux: 7,
            unreachable: 3
        }, {
            period: '2012-02-24 06:30',
            windows: 54,
            linux: 12,
            unreachable: 14
        }, {
            period: '2012-02-24 06:45',
            windows: 112,
            linux: 27,
            unreachable: 4
        }, {
            period: '2012-02-24 07:00',
            windows: 140,
            linux: 57,
            unreachable: 3
        }, {
            period: '2012-02-24 07:15',
            windows: 70,
            linux: 90,
            unreachable: 70
        }, {
            period: '2012-02-24 07:30',
            windows: 140,
            linux: 110,
            unreachable: 0
        }, {
            period: '2012-02-24 07:45',
            windows: 120,
            linux: 80,
            unreachable: 0
        }, {
            period: '2012-02-24 08:00',
            windows: 120,
            linux: 67,
            unreachable: 13
        }],
        xkey: 'period',
        ykeys: ['linux', 'unreachable', 'windows'],
        labels: ['Linux', 'unreachable', 'Windows'],
        pointSize: 2,
        hideHover: 'auto',
        resize: true
    });

});
