<?php
session_start();
include 'db.php';

// 🔒 1. إعدادات الحماية وبيانات الدخول
$admin_user = "admin";
$admin_pass = "hakee@2026"; 

// 🚪 2. تسجيل الخروج
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: admin.php");
    exit();
}

// 🔑 3. معالجة محاولة تسجيل الدخول
$login_error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_btn'])) {
    $user_input = trim($_POST['username'] ?? '');
    $pass_input = trim($_POST['password'] ?? '');

    // مقارنة مباشرة لضمان الدخول بدون مشاكل
    if ($user_input === $admin_user && $pass_input === $admin_pass) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_ip'] = $_SERVER['REMOTE_ADDR'];
        header("Location: admin.php");
        exit();
    } else {
        $login_error = "اسم المستخدم أو كلمة المرور غير صحيحة!";
    }
}

// 🛡️ 4. التحقق من صلاحية الوصول
$is_admin = isset($_SESSION['admin_logged_in']) 
            && $_SESSION['admin_logged_in'] === true 
            && isset($_SESSION['admin_ip']) 
            && $_SESSION['admin_ip'] === $_SERVER['REMOTE_ADDR'];

// إذا لم يكن مسجلاً، يتم عرض شاشة الدخول فقط
if (!$is_admin):
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول الأدمن | حكي جالس</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Cairo', sans-serif; }
        body { background: #0f172a; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 15px; }
        .login-card { background: #1e293b; border-radius: 12px; padding: 30px 25px; width: 100%; max-width: 380px; box-shadow: 0 10px 25px rgba(0,0,0,0.4); border: 1px solid #334155; text-align: center; }
        .login-card h2 { color: #38bdf8; font-size: 20px; margin-bottom: 20px; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .form-group { margin-bottom: 15px; text-align: right; }
        .form-group label { display: block; font-size: 12px; margin-bottom: 5px; color: #cbd5e1; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #475569; background: #0f172a; color: #fff; outline: none; font-size: 13px; }
        .form-group input:focus { border-color: #38bdf8; }
        .btn-submit { width: 100%; padding: 11px; background: #16a34a; border: none; border-radius: 8px; color: #fff; font-weight: bold; cursor: pointer; font-size: 14px; margin-top: 10px; transition: 0.2s; }
        .btn-submit:hover { opacity: 0.9; }
        .error-msg { background: #ef4444; color: #fff; padding: 10px; border-radius: 8px; font-size: 12px; margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="login-card">
        <h2><i class="fa-solid fa-lock"></i> لوحة تحكم الإدارة</h2>
        
        <?php if (!empty($login_error)): ?>
            <div class="error-msg"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $login_error; ?></div>
        <?php endif; ?>

        <form method="POST" action="admin.php">
            <div class="form-group">
                <label>اسم المستخدم</label>
                <input type="text" name="username" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" name="login_btn" class="btn-submit"><i class="fa-solid fa-right-to-bracket"></i> دخول</button>
        </form>
    </div>

</body>
</html>
<?php 
exit(); // 🛑 إيقاف أي تنفيذ آخر للحماية
endif; 

// ==========================================
// ⚙️ معالجة العمليات الحساسة (للأدمن فقط)
// ==========================================

// 🗑️ معالجة حذف مقال
if (isset($_GET['delete_article'])) {
    $id = intval($_GET['delete_article']);
    $conn->query("DELETE FROM articles WHERE id = $id");
    header("Location: admin.php?msg=art_deleted");
    exit();
}

// 🗑️ معالجة حذف فيديو
if (isset($_GET['delete_video'])) {
    $id = intval($_GET['delete_video']);
    $conn->query("DELETE FROM videos WHERE id = $id");
    header("Location: admin.php?msg=vid_deleted");
    exit();
}

// 🗑️ معالجة حذف إعلان
if (isset($_GET['delete_ad'])) {
    $id = intval($_GET['delete_ad']);
    $conn->query("DELETE FROM ads WHERE id = $id");
    header("Location: admin.php?msg=ad_deleted");
    exit();
}

// ➕ معالجة إضافة مقال جديد
if (isset($_POST['add_article'])) {
    $title    = $conn->real_escape_string($_POST['title']);
    $category = $conn->real_escape_string($_POST['category']);
    $content  = $conn->real_escape_string($_POST['content']);
    $conn->query("INSERT INTO articles (title, category, content) VALUES ('$title', '$category', '$content')");
    header("Location: admin.php?msg=art_added");
    exit();
}

// ➕ معالجة إضافة فيديو جديد
if (isset($_POST['add_video'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $url   = $conn->real_escape_string($_POST['url']);
    $conn->query("INSERT INTO videos (title, url) VALUES ('$title', '$url')");
    header("Location: admin.php?msg=vid_added");
    exit();
}

// ➕ معالجة إضافة إعلان جديد
if (isset($_POST['add_ad'])) {
    $title      = $conn->real_escape_string($_POST['ad_title']);
    $desc       = $conn->real_escape_string($_POST['ad_desc']);
    $link       = $conn->real_escape_string($_POST['ad_link']);
    $days       = intval($_POST['ad_duration']);
    $expires_at = date('Y-m-d H:i:s', strtotime("+$days days"));

    $conn->query("INSERT INTO ads (title, description, link, expires_at) VALUES ('$title', '$desc', '$link', '$expires_at')");
    header("Location: admin.php?msg=ad_added");
    exit();
}

// ⚡ معالجة تحديث شريط العاجل
if (isset($_POST['update_ticker'])) {
    $ticker_text = $conn->real_escape_string($_POST['ticker_text']);
    $conn->query("INSERT INTO ticker (content) VALUES ('$ticker_text')");
    header("Location: admin.php?msg=ticker_updated");
    exit();
}

// 📊 جلب الإحصائيات
$total_visits_query = $conn->query("SELECT SUM(visit_count) as total FROM site_visits");
$total_visits_row   = $total_visits_query ? $total_visits_query->fetch_assoc() : null;
$total_visits       = $total_visits_row['total'] ?? 0;

$articles_count_q = $conn->query("SELECT COUNT(*) as count FROM articles");
$articles_count   = $articles_count_q ? $articles_count_q->fetch_assoc()['count'] : 0;

$videos_count_q   = $conn->query("SELECT COUNT(*) as count FROM videos");
$videos_count     = $videos_count_q ? $videos_count_q->fetch_assoc()['count'] : 0;

$ads_count_q      = $conn->query("SELECT COUNT(*) as count FROM ads WHERE expires_at > NOW()");
$ads_count        = $ads_count_q ? $ads_count_q->fetch_assoc()['count'] : 0;

// جلب البيانات للإدارة
$all_articles = $conn->query("SELECT * FROM articles ORDER BY id DESC");
$all_videos   = $conn->query("SELECT * FROM videos ORDER BY id DESC");
$all_ads      = $conn->query("SELECT *, DATEDIFF(expires_at, NOW()) as days_left FROM ads ORDER BY id DESC");
$page_visits  = $conn->query("SELECT * FROM site_visits ORDER BY visit_count DESC");

$current_ticker = "";
$ticker_query   = $conn->query("SELECT content FROM ticker ORDER BY id DESC LIMIT 1");
if ($ticker_query && $ticker_query->num_rows > 0) {
    $current_ticker = $ticker_query->fetch_assoc()['content'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الإدارة | حكي جالس</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #334155;
            --text-muted: #64748b;
            --primary: #1e3a8a;
            --border-color: #cbd5e1;
        }

        body.dark-theme {
            --bg-body: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --primary: #38bdf8;
            --border-color: #334155;
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family:'Cairo', sans-serif; transition: background-color 0.3s, color 0.3s; }
        body { background-color: var(--bg-body); color: var(--text-main); padding: 20px; }
        .admin-container { max-width: 1000px; margin: 0 auto; }
        
        .admin-header { background: var(--card-bg); padding: 20px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .admin-header h2 { color: var(--primary); font-size: 22px; display: flex; align-items: center; gap: 8px; }
        .header-btns { display: flex; gap: 10px; align-items: center; }
        
        .btn-action { text-decoration: none; padding: 8px 14px; border-radius: 20px; font-weight: bold; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; border: none; cursor: pointer; }
        .btn-view { background: #e2e8f0; color: #0f172a; }
        .btn-theme { background: #334155; color: #fff; }
        .btn-logout { background: #dc2626; color: #fff; }

        /* 📊 كروت الإحصائيات */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: var(--card-bg); padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-right: 5px solid var(--primary); display: flex; justify-content: space-between; align-items: center; }
        .stat-card.visits { border-color: #16a34a; }
        .stat-card.articles { border-color: #0284c7; }
        .stat-card.videos { border-color: #dc2626; }
        .stat-card.ads { border-color: #f59e0b; }
        .stat-info h3 { font-size: 13px; color: var(--text-muted); }
        .stat-info span { font-size: 26px; font-weight: 800; color: var(--text-main); }
        .stat-icon { font-size: 30px; opacity: 0.3; }

        .form-card { background: var(--card-bg); padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .form-card h3 { color: var(--text-main); margin-bottom: 15px; font-size: 18px; border-bottom: 2px solid var(--border-color); padding-bottom: 8px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-size: 13px; font-weight: bold; margin-bottom: 5px; display: block; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; outline: none; font-size: 14px; background: var(--bg-body); color: var(--text-main); }
        
        .btn-submit { background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; font-size: 15px; }
        .btn-submit:hover { opacity: 0.9; }

        /* 📋 الجداول وإدارة المحتوى */
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        .data-table th, .data-table td { padding: 12px; text-align: right; border-bottom: 1px solid var(--border-color); }
        .data-table th { background: var(--bg-body); color: var(--text-muted); }
        .btn-delete { background: #ef4444; color: white; padding: 5px 10px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: bold; }
        .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .status-active { background: #dcfce7; color: #15803d; }
        .status-expired { background: #fee2e2; color: #b91c1c; }

        .alert { background: #dcfce7; color: #166534; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>

<div class="admin-container">

    <div class="admin-header">
        <h2><i class="fa-solid fa-user-gear"></i> لوحة تحكّم الإدارة</h2>
        <div class="header-btns">
            <button class="btn-action btn-theme" onclick="toggleDark()"><i class="fa-solid fa-moon"></i> الوضع الليلي</button>
            <a href="index.php" target="_blank" class="btn-action btn-view"><i class="fa-solid fa-globe"></i> المعاينة</a>
            <a href="admin.php?action=logout" class="btn-action btn-logout"><i class="fa-solid fa-right-from-bracket"></i> خروج</a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert">
            <i class="fa-solid fa-circle-check"></i> تم تنفيذ العملية بنجاح!
        </div>
    <?php endif; ?>

    <!-- 📊 الإحصائيات -->
    <div class="stats-grid">
        <div class="stat-card visits">
            <div class="stat-info">
                <h3>إجمالي الزيارات</h3>
                <span><?php echo number_format($total_visits); ?></span>
            </div>
            <i class="fa-solid fa-chart-line stat-icon" style="color:#16a34a"></i>
        </div>

        <div class="stat-card articles">
            <div class="stat-info">
                <h3>المقالات المنشورة</h3>
                <span><?php echo number_format($articles_count); ?></span>
            </div>
            <i class="fa-solid fa-newspaper stat-icon" style="color:#0284c7"></i>
        </div>

        <div class="stat-card videos">
            <div class="stat-info">
                <h3>الفيديوهات واللقاءات</h3>
                <span><?php echo number_format($videos_count); ?></span>
            </div>
            <i class="fa-solid fa-video stat-icon" style="color:#dc2626"></i>
        </div>

        <div class="stat-card ads">
            <div class="stat-info">
                <h3>الإعلانات النشطة</h3>
                <span><?php echo number_format($ads_count); ?></span>
            </div>
            <i class="fa-solid fa-bullhorn stat-icon" style="color:#f59e0b"></i>
        </div>
    </div>

    <!-- ⚡ تحديث شريط الأخبار العاجلة -->
    <div class="form-card">
        <h3><i class="fa-solid fa-bolt" style="color: #dc2626;"></i> تحديث شريط الأخبار العاجلة</h3>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="ticker_text" value="<?php echo htmlspecialchars($current_ticker); ?>" placeholder="أدخل نص الخبر العاجل هنا..." required>
            </div>
            <button type="submit" name="update_ticker" class="btn-submit" style="background:#dc2626;">نشر الخبر العاجل</button>
        </form>
    </div>

    <!-- 📢 إضافة وتنظيم الإعلانات -->
    <div class="form-card">
        <h3><i class="fa-solid fa-rectangle-ad" style="color:#f59e0b;"></i> نشر إعلان جديد</h3>
        <form method="POST">
            <div class="form-group">
                <label>عنوان الإعلان / المحل:</label>
                <input type="text" name="ad_title" placeholder="مثال: مطعم زحلة - العروض الخاصة" required>
            </div>
            <div class="form-group">
                <label>نص / تفاصيل الإعلان:</label>
                <textarea name="ad_desc" rows="2" placeholder="أدخل تفاصيل العرض أو الخصم..." required></textarea>
            </div>
            <div class="form-group">
                <label>رابط التواصل أو واتساب الإعلان:</label>
                <input type="url" name="ad_link" placeholder="https://wa.me/961xxxxxxx">
            </div>
            <div class="form-group">
                <label>مدة عرض الإعلان:</label>
                <select name="ad_duration" required>
                    <option value="7">أسبوع واحد (7 أيام)</option>
                    <option value="15">15 يوماً</option>
                    <option value="30" selected>شهر كامل (30 يوماً)</option>
                    <option value="60">شهريين (60 يوماً)</option>
                    <option value="365">سنة كاملة</option>
                </select>
            </div>
            <button type="submit" name="add_ad" class="btn-submit" style="background:#f59e0b;">نشر الإعلان</button>
        </form>
    </div>

    <!-- 📋 إدارة الإعلانات المنشورة -->
    <div class="form-card">
        <h3><i class="fa-solid fa-list" style="color:#f59e0b;"></i> جدول الإعلانات والحالة</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>اسم الإعلان</th>
                    <th>تاريخ الانتهاء</th>
                    <th>المتبقي</th>
                    <th>الحالة</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($all_ads && $all_ads->num_rows > 0): ?>
                    <?php while($ad = $all_ads->fetch_assoc()): 
                        $is_active = $ad['days_left'] >= 0;
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($ad['title']); ?></strong></td>
                            <td><?php echo date('Y-m-d', strtotime($ad['expires_at'])); ?></td>
                            <td><?php echo $is_active ? $ad['days_left'] . ' يوم' : 'منتهي'; ?></td>
                            <td>
                                <?php if($is_active): ?>
                                    <span class="status-badge status-active">نشط</span>
                                <?php else: ?>
                                    <span class="status-badge status-expired">منتهي</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="admin.php?delete_ad=<?php echo $ad['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت تأكد من حذف هذا الإعلان؟')"><i class="fa-solid fa-trash"></i> حذف</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">لا يوجد إعلانات منشورة حالياً.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ➕ إضافة مقال جديد -->
    <div class="form-card">
        <h3><i class="fa-solid fa-pen-to-square"></i> إضافة مقال جديد</h3>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="title" placeholder="عنوان المقال..." required>
            </div>
            <div class="form-group">
                <select name="category" required>
                    <option value="سياسة">سياسة</option>
                    <option value="بيئة">بيئة</option>
                    <option value="زحلة والبقاع" selected>زحلة والبقاع</option>
                    <option value="عام">عام</option>
                </select>
            </div>
            <div class="form-group">
                <textarea name="content" rows="4" placeholder="محتوى المقال..." required></textarea>
            </div>
            <button type="submit" name="add_article" class="btn-submit">نشر المقال</button>
        </form>
    </div>

    <!-- ➕ إضافة فيديو جديد -->
    <div class="form-card">
        <h3><i class="fa-solid fa-video"></i> إضافة فيديو جديد</h3>
        <form method="POST">
            <div class="form-group">
                <input type="text" name="title" placeholder="عنوان الفيديو..." required>
            </div>
            <div class="form-group">
                <input type="url" name="url" placeholder="رابط الفيديو (Facebook / YouTube)..." required>
            </div>
            <button type="submit" name="add_video" class="btn-submit" style="background:#2563eb;">نشر الفيديو</button>
        </form>
    </div>

    <!-- 🗑️ إدارة المقالات المنشورة -->
    <div class="form-card">
        <h3><i class="fa-solid fa-list-check"></i> إدارة المقالات</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>العنوان</th>
                    <th>التصنيف</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($all_articles && $all_articles->num_rows > 0): ?>
                    <?php while($art = $all_articles->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($art['title']); ?></td>
                            <td><span class="status-badge" style="background:#e2e8f0;"><?php echo htmlspecialchars($art['category'] ?? 'عام'); ?></span></td>
                            <td>
                                <a href="admin.php?delete_article=<?php echo $art['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت تأكد من حذف هذا المقال؟')"><i class="fa-solid fa-trash"></i> حذف</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3">لا يوجد مقالات حالياً.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 🗑️ إدارة الفيديوهات المنشورة -->
    <div class="form-card">
        <h3><i class="fa-solid fa-film"></i> إدارة الفيديوهات</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>عنوان الفيديو</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($all_videos && $all_videos->num_rows > 0): ?>
                    <?php while($vid = $all_videos->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vid['title']); ?></td>
                            <td>
                                <a href="admin.php?delete_video=<?php echo $vid['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت تأكد من حذف هذا الفيديو؟')"><i class="fa-solid fa-trash"></i> حذف</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="2">لا يوجد فيديوهات حالياً.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 📈 إحصائيات التصفح حسب القسم -->
    <div class="form-card">
        <h3><i class="fa-solid fa-chart-pie"></i> تفاصيل زيارات أقسام الموقع</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>القسم</th>
                    <th>عدد الزيارات</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($page_visits && $page_visits->num_rows > 0): ?>
                    <?php while($pv = $page_visits->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($pv['page_name']); ?></strong></td>
                            <td><?php echo number_format($pv['visit_count']); ?> زيارة</td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
    function toggleDark() {
        document.body.classList.toggle('dark-theme');
        let isDark = document.body.classList.contains('dark-theme');
        localStorage.setItem('admin_theme', isDark ? 'dark' : 'light');
    }

    if (localStorage.getItem('admin_theme') === 'dark') {
        document.body.classList.add('dark-theme');
    }
</script>

</body>
</html>