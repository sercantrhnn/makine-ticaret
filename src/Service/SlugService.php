<?php

namespace App\Service;

class SlugService
{
    /**
     * Türkçe karakterleri ve özel karakterleri SEO dostu slug'a çevirir
     */
    public function slugify(string $text, string $locale = 'tr'): string
    {
        // Türkçe karakterleri değiştir
        $turkishChars = [
            'ç' => 'c', 'Ç' => 'C',
            'ğ' => 'g', 'Ğ' => 'G',
            'ı' => 'i', 'I' => 'I',
            'İ' => 'I', 'i' => 'i',
            'ö' => 'o', 'Ö' => 'O',
            'ş' => 's', 'Ş' => 'S',
            'ü' => 'u', 'Ü' => 'U'
        ];
        
        $text = strtr($text, $turkishChars);
        
        // Küçük harfe çevir
        $text = strtolower($text);
        
        // Özel karakterleri kaldır ve tire ile değiştir
        $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
        
        // Çoklu tireleri tek tire yap
        $text = preg_replace('/-+/', '-', $text);
        
        // Baş ve sondaki tireleri kaldır
        $text = trim($text, '-');
        
        return $text;
    }
    
    /**
     * Maksimum uzunlukla slug oluştur
     */
    public function createSlugWithLimit(string $text, int $maxLength = 50): string
    {
        $slug = $this->slugify($text);
        
        if (strlen($slug) > $maxLength) {
            // Kelime sınırında kes
            $slug = substr($slug, 0, $maxLength);
            $lastSpace = strrpos($slug, '-');
            if ($lastSpace !== false) {
                $slug = substr($slug, 0, $lastSpace);
            }
        }
        
        return $slug;
    }
    
    /**
     * Benzersiz slug oluştur (ID ile)
     */
    public function createUniqueSlug(string $text, int $id): string
    {
        $baseSlug = $this->createSlugWithLimit($text, 40);
        return $baseSlug . '-' . $id;
    }
}
