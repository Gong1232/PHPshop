<?php
require '../include/base.php';
Base::auth(); // Require login to view products

// Get product ID from URL
$product_id = (int)Base::get('id');
if (!$product_id) {
    Base::notFound();
}

// Database connection
$db = Base::getDB();

try {
    // Get product details
    $stmt = $db->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        Base::notFound();
    }

    // Get related products (same category)
    $stmt = $db->prepare("SELECT * FROM products 
                         WHERE product_id != ? AND category_id = ? AND stock > 0 
                         ORDER BY RAND() LIMIT 4");
    $stmt->execute([$product_id, $product['category_id']]);
    $related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get product reviews
    $stmt = $db->prepare("SELECT reviews.*, users.username 
                         FROM reviews 
                         JOIN users ON reviews.user_id = users.user_id
                         WHERE product_id = ? 
                         ORDER BY created_at DESC");
    $stmt->execute([$product_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate average rating
    $avg_rating = 0;
    if (!empty($reviews)) {
        $total = array_reduce($reviews, function ($carry, $review) {
            return $carry + $review['rating'];
        }, 0);
        $avg_rating = round($total / count($reviews), 1);
    }
} catch (PDOException $e) {
    Base::logError($e->getMessage());
    Base::redirect('/error.php'); // Absolute path
}

// Set page title
$_title = Base::sanitize($product['name']) . ' - PHPShop';
include '../include/header.php';
?>

<main class="product-page">
    <link rel="stylesheet" href="/css/product.css"> <!-- Root path -->
    <div class="container">
        <section class="product-details">
            <div class="product-images">
                <img src="/uploads/products/<?= Base::sanitize($product['image_path'] ?? 'default.jpg') ?>"
                    alt="<?= Base::sanitize($product['name']) ?>" class="main-image">
            </div>
            <div class="product-info">
                <h1><?= Base::sanitize($product['name']) ?></h1>
                <div class="price-rating">
                    <span class="price">$<?= number_format($product['price'], 2) ?></span>
                    <?php if ($avg_rating > 0): ?>
                        <div class="rating">
                            <span class="stars"><?= str_repeat('★', (int)$avg_rating) ?><?= str_repeat('☆', 5 - (int)$avg_rating) ?></span>
                            <span class="avg"><?= $avg_rating ?> (<?= count($reviews) ?> reviews)</span>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="description"><?= Base::sanitize($product['description']) ?></p>
                <div class="stock-status">
                    <?php if ($product['stock'] > 0): ?>
                        <span class="in-stock">In Stock (<?= $product['stock'] ?> available)</span>
                    <?php else: ?>
                        <span class="out-of-stock">Out of Stock</span>
                    <?php endif; ?>
                </div>
                <form action="/add-to-cart.php" method="POST" class="add-to-cart">
                    <input type="hidden" name="csrf_token" value="<?= Base::csrfToken() ?>">
                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                    <div class="quantity">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" name="quantity"
                            min="1" max="<?= $product['stock'] ?>" value="1">
                    </div>
                    <button type="submit" class="btn btn-primary"
                        <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                        Add to Cart
                    </button>
                </form>
            </div>
        </section>

        <section class="product-reviews">
            <h2>Customer Reviews</h2>
            <?php if (!empty($reviews)): ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review">
                            <div class="review-header">
                                <span class="username"><?= Base::sanitize($review['username']) ?></span>
                                <span class="rating"><?= str_repeat('★', $review['rating']) ?><?= str_repeat('☆', 5 - $review['rating']) ?></span>
                                <span class="date"><?= date('M j, Y', strtotime($review['created_at'])) ?></span>
                            </div>
                            <p class="comment"><?= Base::sanitize($review['comment']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No reviews yet. Be the first to review this product!</p>
            <?php endif; ?>

            <?php if (Base::userHasPurchased($product_id)): ?>
                <form action="/submit-review.php" method="POST" class="review-form">
                    <input type="hidden" name="csrf_token" value="<?= Base::csrfToken() ?>">
                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                    <div class="form-group">
                        <label>Your Rating</label>
                        <select name="rating" required>
                            <option value="">Select rating</option>
                            <option value="5">★★★★★</option>
                            <option value="4">★★★★☆</option>
                            <option value="3">★★★☆☆</option>
                            <option value="2">★★☆☆☆</option>
                            <option value="1">★☆☆☆☆</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Your Review</label>
                        <textarea name="comment" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-secondary">Submit Review</button>
                </form>
            <?php elseif (Base::isLoggedIn()): ?>
                <div class="purchase-notice">
                    <p>You need to purchase this product before leaving a review.</p>
                    <a href="/products.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!empty($related_products)): ?>
            <section class="related-products">
                <h2>You May Also Like</h2>
                <div class="product-grid">
                    <?php foreach ($related_products as $product): ?>
                        <div class="product-card">
                            <img src="/uploads/products/<?= Base::sanitize($product['image_path'] ?? 'default.jpg') ?>"
                                alt="<?= Base::sanitize($product['name']) ?>">
                            <h3><?= Base::sanitize($product['name']) ?></h3>
                            <p class="price">$<?= number_format($product['price'], 2) ?></p>
                            <a href="/app/product/product.php?id=<?= $product['product_id'] ?>" class="btn btn-secondary">View Details</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php include '../include/footer.php'; ?> <!-- Fixed path -->