<?php

define('TITLE', 'Tìm kiếm Trích dẫn');

require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/footer.php';

$has_access = ensure_admin_access();
$error_message = null;
$reason = null;
$sources = [];
$quotes = [];

if ($has_access) {
    try {
        $pdo = get_database_connection();
        $source_statement = $pdo->prepare('SELECT DISTINCT source FROM quotes ORDER BY source ASC');
        $source_statement->execute();
        $sources = $source_statement->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $error_message = 'Không thể lấy danh sách nguồn';
        $reason = $e->getMessage();
    }

    $search_keyword = trim($_GET['keyword'] ?? '');
    $selected_source = trim($_GET['source'] ?? '');

    if ($search_keyword !== '' || $selected_source !== '') {
        $query = 'SELECT id, quote, source, favorite FROM quotes WHERE 1=1';
        $params = [];

        if ($search_keyword !== '') {
            $query .= ' AND quote LIKE ?';
            $params[] = '%' . $search_keyword . '%';
        }

        if ($selected_source !== '') {
            $query .= ' AND source = ?';
            $params[] = $selected_source;
        }

        $query .= ' ORDER BY date_entered DESC';

        try {
            $statement = $pdo->prepare($query);
            $statement->execute($params);
            $quotes = $statement->fetchAll();
        } catch (PDOException $e) {
            $error_message = 'Không thể thực hiện tìm kiếm';
            $reason = $e->getMessage();
        }
    }
} else {
    $error_message = 'Bạn không có quyền truy cập trang này';
}
?>

<?php render_page_header(); ?>

<h2>Tìm kiếm Trích dẫn</h2>

<?php if (!empty($error_message)): ?>
    <?php include __DIR__ . '/../partials/show_error.php'; ?>
<?php endif; ?>

<?php if ($has_access): ?>
    <form action="search.php" method="get">
        <p>
            <label>Từ khóa trích dẫn:
                <input type="text" name="keyword" value="<?= html_escape($_GET['keyword'] ?? '') ?>">
            </label>
        </p>
        <p>
            <label>Nguồn / Tác giả:
                <select name="source">
                    <option value="">-- Tất cả các nguồn --</option>
                    <?php foreach ($sources as $src): ?>
                        <option value="<?= html_escape($src) ?>" <?= (isset($_GET['source']) && $_GET['source'] === $src) ? 'selected' : '' ?>>
                            <?= html_escape($src) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </p>
        <p><input type="submit" name="submit" value="Tìm kiếm"></p>
    </form>

    <hr>

    <?php if (isset($_GET['submit'])): ?>
        <h3>Kết quả tìm kiếm</h3>
        <?php if (!empty($quotes)): ?>
            <?php foreach ($quotes as $quote): ?>
                <div>
                    <blockquote><?= html_escape($quote['quote']) ?></blockquote>
                    <p><?= html_escape($quote['source']) ?>
                        <?php if (!empty($quote['favorite'])): ?>
                            <strong> | Yêu thích!</strong>
                        <?php endif; ?>
                    </p>
                    <p>
                        <strong>Quản trị Trích dẫn:</strong>
                        <a href="edit_quote.php?id=<?= urlencode($quote['id']) ?>">Sửa</a> &lt;-&gt;
                        <a href="delete_quote.php?id=<?= urlencode($quote['id']) ?>">Xóa</a>
                    </p>
                </div>
                <hr>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Không tìm thấy trích dẫn nào phù hợp.</p>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<p>
    <a href="index.php">Trang chủ</a> &lt;-&gt;
    <a href="view_quotes.php">Quản lý Trích dẫn</a>
</p>

<?php render_page_footer(); ?>