'use strict';

statisticsApp
    .controller("statisticsViewController", ["$scope",
        function($scope, portfolioManager, $filter) {
        $scope.selectedPortfolioId = 0;
        $scope.selectedPortfolio = null;
        $scope.period = {
            date: {startDate: moment().startOf('month'), endDate: moment().endOf('month')}
        };

        $scope.cosPoints = [];
        for (var i=0; i<2*Math.PI; i+=0.4){
            $scope.cosPoints.push([i, Math.cos(i)]);
        }

        $scope.chartData = [];

        $scope.chartOptions = {
            series: [
                {
                    lineWidth: 2,
                    markerOptions: { style:"filledSquare", size: 5 }
                }
            ]
        };

        $scope.fetchVisitData = function() {
            if ($scope.selectedPortfolio !== null) {
                $('#chart').replaceWith('<div id="chart"></div>');
                $scope.chartData = [$scope.cosPoints];
                $.jqplot('chart', $scope.chartData, $scope.chartOptions);
            }
        };
    }]);