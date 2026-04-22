package com.ecommerce.ai.controller;

import com.ecommerce.ai.entity.Product;
import com.ecommerce.ai.service.ProductService;
import java.util.List;
import org.springframework.web.bind.annotation.CrossOrigin;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/api")
@CrossOrigin(origins = "*")
public class ProductController {

    private final ProductService productService;

    public ProductController(ProductService productService) {
        this.productService = productService;
    }

    @GetMapping("/search")
    public List<Product> search(@RequestParam String q) {
        return productService.searchProducts(q);
    }

    @GetMapping("/recommend")
    public List<Product> recommendProducts(@RequestParam("category") String category) {
        return productService.recommendProducts(category);
    }

    @GetMapping("/filter")
    public List<Product> filterByPrice(@RequestParam("maxPrice") double maxPrice) {
        return productService.filterByPrice(maxPrice);
    }
}
