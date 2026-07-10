<?php

namespace App\Enums;

enum PromotionCriteriaType: string
{
    case RankThreshold = 'rank_threshold';
    case AverageThreshold = 'average_threshold';
}
