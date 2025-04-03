<?php
require 'include/base.php';

// Set page title
$_title = 'Home - PHPShop';

// Database connection
$db = Base::getDB();

// Get featured products
try {
    $stmt = $db->query("SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC LIMIT 6");
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    Base::logError($e->getMessage());
    $featured_products = [];
}

include 'include/header.php';
?>

<main class="home-page">
    <!-- Hero Section -->
    <section class="hero">  
        <div class="container">
            <h1>Welcome to PHPShop</h1>
            <p>Discover amazing products at great prices</p>
            <a href="include/check-auth.php?redirect=products" class="btn btn-primary">Shop Now</a>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="featured-products">
        <div class="container">
            <h2>Featured Products</h2>
            <div class="product-grid">
                <?php if (!empty($featured_products)): ?>
                    <?php foreach ($featured_products as $product): ?>
                        <div class="product-card">
                            <img src="/uploads/products/<?= Base::sanitize($product['image_path'] ?? 'default.jpg') ?>"
                                alt="<?= Base::sanitize($product['name']) ?>">
                            <h3><?= Base::sanitize($product['name']) ?></h3>
                            <p class="price">$<?= number_format($product['price'], 2) ?></p>
                            <a href="product/product.php?id=<?= $product['product_id'] ?>" class="btn btn-secondary">View Details</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No featured products available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Categories -->
    <section class="categories">
        <div class="container">
            <h2>Shop by Category</h2>
            <div class="category-grid">
                <?php
                $categories = $db->query("SELECT * FROM categories LIMIT 4")->fetchAll();
                foreach ($categories as $category): ?>
                    <a href="/products.php?category=<?= $category['category_id'] ?>" class="category-card">
                        <h3><?= Base::sanitize($category['name']) ?></h3>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php include 'include/footer.php'; ?>