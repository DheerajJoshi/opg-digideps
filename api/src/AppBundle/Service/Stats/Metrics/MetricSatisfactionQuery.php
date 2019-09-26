<?php

namespace AppBundle\Service\Stats\Metrics;

class MetricSatisfactionQuery extends MetricQuery
{
    protected $aggregation = 'AVG(val)';
    protected $supportedDimensions = ['deputyType', 'reportType'];

    /**
     * @return string
     */
    protected function getSubquery()
    {
        return "SELECT
            s.created_at date,
            CASE
                WHEN s.deputy_role LIKE '%_PROF_%' THEN 'prof'
                WHEN s.deputy_role LIKE '%_PA_%' THEN 'pa'
                ELSE 'lay'
            END deputyType,
            report_type reportType,
            s.score val
        FROM satisfaction s";
    }

}
