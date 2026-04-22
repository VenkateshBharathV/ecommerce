<?php

class SearchService
{
    public function parseSmartQuery(string $query): array
    {
        $clean = trim(mb_strtolower($query));
        $intent = [
            'query' => trim($query),
            'keywords' => [],
            'category' => null,
            'max_price' => null,
            'min_price' => null,
        ];

        if ($clean === '') {
            return $intent;
        }

        $categoryMap = [
            'mobile' => 'Mobiles',
            'mobiles' => 'Mobiles',
            'phone' => 'Mobiles',
            'phones' => 'Mobiles',
            'electronics' => 'Electronics',
            'electronic' => 'Electronics',
            'fashion' => 'Fashion',
            'book' => 'Books',
            'books' => 'Books',
            'appliance' => 'Appliances',
            'appliances' => 'Appliances',
            'sport' => 'Sports',
            'sports' => 'Sports',
            'laptop' => 'Electronics',
            'laptops' => 'Electronics',
            'shoe' => 'Fashion',
            'shoes' => 'Fashion',
        ];

        foreach ($categoryMap as $needle => $category) {
            if (preg_match('/\b' . preg_quote($needle, '/') . '\b/', $clean)) {
                $intent['category'] = $category;
                break;
            }
        }

        if (preg_match('/(?:under|below|less than|max)\s*(?:rs\.?|rupees|inr|₹)?\s*([0-9,]+)/i', $query, $matches)) {
            $intent['max_price'] = (float) str_replace(',', '', $matches[1]);
        }

        if (preg_match('/(?:above|over|more than|min)\s*(?:rs\.?|rupees|inr|₹)?\s*([0-9,]+)/i', $query, $matches)) {
            $intent['min_price'] = (float) str_replace(',', '', $matches[1]);
        }

        $keywordString = preg_replace('/(?:under|below|less than|max|above|over|more than|min|show me|find|search|for|products?|items?)/i', ' ', $query);
        $keywordString = preg_replace('/(?:rs\.?|rupees|inr|₹)\s*[0-9,]+/i', ' ', $keywordString);
        $keywordString = preg_replace('/[^\w\s]/', ' ', $keywordString);
        $parts = preg_split('/\s+/', strtolower(trim((string) $keywordString))) ?: [];
        $stopWords = ['me', 'show', 'find', 'search', 'for', 'product', 'products', 'item', 'items', 'under', 'below', 'less', 'than', 'above', 'over', 'more', 'max', 'min'];

        foreach ($parts as $part) {
            if ($part !== '' && !in_array($part, $stopWords, true) && !is_numeric($part)) {
                $intent['keywords'][] = $part;
            }
        }

        $intent['keywords'] = array_values(array_unique($intent['keywords']));

        return $intent;
    }

    public function searchProducts(PDO $conn, string $query, int $limit = 12): array
    {
        $intent = $this->parseSmartQuery($query);
        $where = [];
        $params = [];

        if ($intent['category']) {
            $where[] = 'category = ?';
            $params[] = $intent['category'];
        }

        if ($intent['min_price'] !== null) {
            $where[] = 'price >= ?';
            $params[] = $intent['min_price'];
        }

        if ($intent['max_price'] !== null) {
            $where[] = 'price <= ?';
            $params[] = $intent['max_price'];
        }

        if (!empty($intent['keywords'])) {
            $keywordConditions = [];
            foreach ($intent['keywords'] as $keyword) {
                $keywordConditions[] = '(name LIKE ? OR description LIKE ?)';
                $params[] = '%' . $keyword . '%';
                $params[] = '%' . $keyword . '%';
            }
            $where[] = '(' . implode(' AND ', $keywordConditions) . ')';
        } elseif ($intent['query'] !== '') {
            $where[] = '(name LIKE ? OR description LIKE ? OR category LIKE ?)';
            $params[] = '%' . $intent['query'] . '%';
            $params[] = '%' . $intent['query'] . '%';
            $params[] = '%' . $intent['query'] . '%';
        }

        $sql = 'SELECT id, name, description, category, price, image FROM products';
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY price ASC, id DESC LIMIT ' . max(1, $limit);

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        return [
            'intent' => $intent,
            'products' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    public function getSuggestions(PDO $conn, string $query, int $limit = 6): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $search = $this->searchProducts($conn, $query, $limit);
        $suggestions = [];

        foreach ($search['products'] as $product) {
            $suggestions[] = [
                'id' => (int) $product['id'],
                'name' => $product['name'],
                'price' => (float) $product['price'],
                'category' => $product['category'] ?? '',
            ];
        }

        return $suggestions;
    }
}
