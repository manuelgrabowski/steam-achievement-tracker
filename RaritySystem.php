<?php

class RaritySystem {
    private static array $rarityTiers = [
        [
            'threshold' => 80,
            'label' => 'Very Common',
            'color' => '#8BC34A',
            'class' => 'very-common'
        ],
        [
            'threshold' => 50,
            'label' => 'Common',
            'color' => '#8BC34A',
            'class' => 'common'
        ],
        [
            'threshold' => 20,
            'label' => 'Uncommon',
            'color' => '#FF9800',
            'class' => 'uncommon'
        ],
        [
            'threshold' => 10,
            'label' => 'Rare',
            'color' => '#9C27B0',
            'class' => 'rare'
        ],
        [
            'threshold' => 5,
            'label' => 'Very Rare',
            'color' => '#E91E63',
            'class' => 'very-rare'
        ],
        [
            'threshold' => 1,
            'label' => 'Ultra Rare',
            'color' => '#F44336',
            'class' => 'ultra-rare'
        ],
        [
            'threshold' => 0,
            'label' => 'Legendary',
            'color' => '#FFD700',
            'class' => 'legendary'
        ]
    ];
    
    public static function getRarity(float $percentage): array {
        foreach (self::$rarityTiers as $tier) {
            if ($percentage >= $tier['threshold']) {
                return $tier;
            }
        }
        
        // Fallback to legendary (should never reach due to 0 threshold)
        return end(self::$rarityTiers);
    }
    
    public static function getLabel(float $percentage): string {
        return self::getRarity($percentage)['label'];
    }
    
    public static function getColor(float $percentage): string {
        return self::getRarity($percentage)['color'];
    }
    
    public static function getClass(float $percentage): string {
        return self::getRarity($percentage)['class'];
    }
}