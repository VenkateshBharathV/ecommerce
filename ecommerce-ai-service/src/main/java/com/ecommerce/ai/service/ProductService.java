package com.ecommerce.ai.service;

import com.ecommerce.ai.entity.Product;
import com.ecommerce.ai.repository.ProductRepository;
import java.util.Collections;
import java.util.List;
import org.springframework.stereotype.Service;

@Service
public class ProductService {

    private final ProductRepository productRepository;

    public ProductService(ProductRepository productRepository) {
        this.productRepository = productRepository;
    }

    public List<Product> searchProducts(String query) {
        if (query == null || query.isBlank()) {
            return productRepository.findAll();
        }

        String normalizedQuery = query.trim();
        if (normalizedQuery.toLowerCase().contains("cheap")) {
            return productRepository.findByPriceLessThanEqual(1000);
        }

        return productRepository.findByNameContainingIgnoreCaseOrDescriptionContainingIgnoreCase(
                normalizedQuery,
                normalizedQuery
        );
    }

    public List<Product> recommendProducts(String category) {
        if (category == null || category.isBlank()) {
            return Collections.emptyList();
        }

        return productRepository.findTop5ByCategoryIgnoreCaseOrderByIdDesc(category.trim());
    }

    public List<Product> filterByPrice(double maxPrice) {
        return productRepository.findByPriceLessThanEqual(maxPrice);
    }
}
