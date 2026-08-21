<?php
include 'db.php';

// 📊 1. حماية وتأمين اسم الصفحة
$allowed_tabs = ['home', 'about', 'videos', 'articles', 'bookmarks', 'contact'];
$raw_tab      = $_GET['tab'] ?? 'home';
$page_name    = in_array($raw_tab, $allowed_tabs, true) ? $raw_tab : 'home';

// 📊 2. تسجيل الزيارات بأمان
if (isset($conn) && $conn) {
    $stmt_check = @$conn->prepare("SELECT visit_count FROM site_visits WHERE page_name = ?");
    if ($stmt_check) {
        $stmt_check->bind_param("s", $page_name);
        $stmt_check->execute();
        $check_visit = $stmt_check->get_result();

        if ($check_visit && $check_visit->num_rows > 0) {
            $stmt_upd = @$conn->prepare("UPDATE site_visits SET visit_count = visit_count + 1 WHERE page_name = ?");
            if ($stmt_upd) {
                $stmt_upd->bind_param("s", $page_name);
                $stmt_upd->execute();
                $stmt_upd->close();
            }
        } else {
            $stmt_ins = @$conn->prepare("INSERT INTO site_visits (page_name, visit_count) VALUES (?, 1)");
            if ($stmt_ins) {
                $stmt_ins->bind_param("s", $page_name);
                $stmt_ins->execute();
                $stmt_ins->close();
            }
        }
        $stmt_check->close();
    }
}

// 📊 3. جلب بيانات الإحصائيات والأخبار العاجلة والمحتوى
$total_visits = 1;
$total_visits_query = @$conn->query("SELECT SUM(visit_count) as total FROM site_visits");
if ($total_visits_query && $row = $total_visits_query->fetch_assoc()) {
    $total_visits = $row['total'] ?? 1;
}

$videos_result   = @$conn->query("SELECT * FROM videos ORDER BY id DESC");
$articles_result = @$conn->query("SELECT * FROM articles ORDER BY id DESC");

$ticker_text = "أهلاً بكم في موقع \"حكي جالس\" - المنبر الإعلامي والسياسي المستقل لمتابعة قضايا زحلة والبقاع.";
$ticker_query = @$conn->query("SELECT content FROM ticker ORDER BY id DESC LIMIT 1");
if ($ticker_query && $ticker_query->num_rows > 0) {
    $ticker_text = $ticker_query->fetch_assoc()['content'];
}

$active_tab   = $page_name;
$cache_buster = time();
$fb_page_url  = "https://www.facebook.com/share/1BgxajECDi/";

function estimate_reading_time($text) {
    $word_count = count(preg_split('~[^\p{L}\p{N}]+~u', strip_tags($text)));
    $minutes = ceil($word_count / 150); 
    return $minutes < 1 ? 1 : $minutes;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حكي جالس | المنبر الإعلامي والسياسي</title>
    
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#1e3a8a">

    <link rel="icon" type="image/jpeg" href="logo.jpg?v=<?php echo $cache_buster; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1e3a8a;
            --accent: #16a34a;
            --dark: #0f172a;
            --light: #f8fafc;
            --card-bg: #ffffff;
            --text-color: #334155;
            --red: #dc2626;
        }

        body.dark-theme {
            --light: #0f172a;
            --card-bg: #1e293b;
            --text-color: #f1f5f9;
            --primary: #38bdf8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Cairo', sans-serif; transition: background-color 0.3s, color 0.3s; }
        body { background-color: var(--light); color: var(--text-color); display: flex; flex-direction: column; min-height: 100vh; }
        
        /* 🔴 شريط الأخبار العاجلة المطوّر */
        .ticker-wrap { 
            background: linear-gradient(90deg, #0f172a 0%, #1e293b 100%); 
            color: #fff; 
            display: flex; 
            align-items: center; 
            font-size: 12px; 
            overflow: hidden; 
            height: 34px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .ticker-title { 
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); 
            padding: 0 12px; 
            height: 100%; 
            display: flex; 
            align-items: center; 
            font-weight: 800; 
            white-space: nowrap; 
            z-index: 2; 
            box-shadow: 2px 0 6px rgba(0,0,0,0.2);
            gap: 6px;
            font-size: 11px;
        }

        .live-pulse {
            width: 7px;
            height: 7px;
            background-color: #ffffff;
            border-radius: 50%;
            display: inline-block;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(255, 255, 255, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); }
        }

        .ticker-content { 
            white-space: nowrap; 
            animation: ticker 30s linear infinite; 
            padding-right: 100%; 
            font-weight: 600;
        }

        .ticker-wrap:hover .ticker-content {
            animation-play-state: paused;
        }

        @keyframes ticker { 
            0% { transform: translate3d(0, 0, 0); } 
            100% { transform: translate3d(100%, 0, 0); } 
        }

        /* 🌟 هيدر مصغر وأنيق لحل مشكلة المساحة */
        header { background: var(--card-bg); box-shadow: 0 2px 8px rgba(0,0,0,0.06); position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .nav-container { max-width: 1000px; margin: 0 auto; padding: 6px 10px; display: flex; flex-direction: column; gap: 6px; align-items: center; }
        
        .brand { display: flex; align-items: center; gap: 8px; text-decoration: none; text-align: center; }
        .brand-logo-img { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent); }
        .brand-text h1 { font-size: 16px; color: var(--primary); font-weight: 800; line-height: 1; }
        .brand-text span { font-size: 10px; color: #64748b; }

        .header-actions { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; justify-content: center; }
        .social-link-fb { background: #1877f2; color: white; padding: 3px 10px; border-radius: 15px; text-decoration: none; font-size: 11px; font-weight: bold; display: flex; align-items: center; gap: 4px; }
        .theme-toggle, .notif-toggle { background: #f1f5f9; border: none; padding: 3px 10px; border-radius: 15px; cursor: pointer; font-size: 11px; font-weight: bold; color: var(--dark); display: flex; align-items: center; gap: 4px; }
        body.dark-theme .theme-toggle, body.dark-theme .notif-toggle { background: #334155; color: #f8fafc; }

        nav ul { display: flex; flex-wrap: wrap; justify-content: center; list-style: none; gap: 4px; width: 100%; }
        nav button { background: #f1f5f9; border: none; padding: 4px 10px; font-size: 11px; font-weight: 700; color: #0f172a; cursor: pointer; border-radius: 20px; transition: 0.2s; display: flex; align-items: center; gap: 4px; }
        body.dark-theme nav button { background: #334155; color: #f1f5f9; }
        nav button:hover, nav button.active { background: var(--primary); color: #fff !important; }

        .main-wrapper { max-width: 1000px; margin: 10px auto; padding: 0 10px; flex: 1; width: 100%; }
        .page-section { display: none; }
        .page-section.active { display: block; }

        /* 🌟 تصغير بطاقة الشخصية والتفاصيل */
        .profile-card { background: var(--card-bg); border-radius: 12px; padding: 10px 12px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); margin-bottom: 12px; border-top: 4px solid var(--accent); }
        .profile-avatar { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
        .profile-info h2 { font-size: 16px; color: var(--primary); }
        .badges { display: flex; gap: 4px; flex-wrap: wrap; justify-content: center; margin-top: 2px; }
        .badge { background: #f1f5f9; color: #1e293b; padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: 700; display: flex; align-items: center; gap: 4px; }
        body.dark-theme .badge { background: #334155; color: #f1f5f9; }
        .badge i { color: var(--accent); }

        .ad-banner-clean {
            background: var(--card-bg);
            border: 1px solid #e2e8f0;
            border-right: 4px solid var(--accent);
            border-radius: 10px;
            padding: 12px 14px;
            margin: 12px 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            position: relative;
        }

        body.dark-theme .ad-banner-clean {
            border-color: #334155;
            border-right-color: var(--accent);
        }

        .ad-badge-clean {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #f1f5f9;
            color: #64748b;
            font-size: 10px;
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: 700;
        }

        body.dark-theme .ad-badge-clean {
            background: #0f172a;
            color: #94a3b8;
        }

        .ad-title-clean {
            font-size: 13px;
            color: var(--primary);
            font-weight: 800;
            margin-bottom: 4px;
            padding-left: 45px;
        }

        .ad-desc-clean {
            font-size: 11px;
            color: var(--text-color);
            line-height: 1.5;
            margin-bottom: 8px;
            opacity: 0.9;
        }

        .ad-btn-clean {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #16a34a;
            color: #fff;
            padding: 5px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 11px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2);
        }
        .ad-btn-clean:hover { opacity: 0.9; }

        .section-header { font-size: 15px; color: var(--primary); margin-bottom: 10px; padding-bottom: 3px; border-bottom: 2px solid var(--accent); display: inline-block; }
        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 10px; }
        .card { background: var(--card-bg); border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.03); padding: 12px; margin-bottom: 10px; }

        .category-filter { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
        .cat-btn { background: #e2e8f0; border: none; padding: 4px 10px; border-radius: 15px; font-size: 11px; font-weight: bold; cursor: pointer; }
        body.dark-theme .cat-btn { background: #334155; color: #f1f5f9; }
        .cat-btn.active { background: var(--accent); color: white; }

        .search-box { width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 10px; font-size: 12px; outline: none; background: var(--card-bg); color: var(--text-color); }
        .search-box:focus { border-color: var(--primary); }

        .meta-info { display: flex; gap: 10px; font-size: 11px; color: #64748b; margin: 4px 0; }

        .share-btn-group { display: flex; gap: 6px; margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0; flex-wrap: wrap; }
        .share-btn { font-size: 11px; padding: 4px 8px; border-radius: 5px; color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; border: none; }
        .share-wa { background: #25d366; }
        .share-fb { background: #1877f2; }
        .share-native { background: #8b5cf6; }
        .btn-audio { background: #0284c7; }
        .btn-size { background: #d97706; }
        .btn-bookmark { background: #ec4899; }

        .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px; margin-bottom: 8px; }
        .video-container iframe { position: absolute; top:0; left: 0; width: 100%; height: 100%; border:0; }

        .interactions-bar { display: flex; align-items: center; justify-content: space-between; margin-top: 8px; padding-top: 8px; border-top: 1px dashed #e2e8f0; }
        .like-btn { background: transparent; border: 1px solid #cbd5e1; padding: 3px 10px; border-radius: 15px; cursor: pointer; color: var(--text-color); display: flex; align-items: center; gap: 4px; font-size: 11px; }
        .like-btn.liked { background: #ef4444; color: white; border-color: #ef4444; }

        .comments-section { margin-top: 8px; background: rgba(0,0,0,0.02); padding: 8px; border-radius: 6px; }
        .comment-input { width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 5px; margin-bottom: 4px; font-size: 12px; }
        .comment-item { font-size: 11px; padding: 4px 0; border-bottom: 1px solid #f1f5f9; }

        .float-wa { position: fixed; bottom: 12px; left: 12px; background: #25d366; color: white; border-radius: 50px; padding: 6px 12px; font-size: 11px; font-weight: bold; text-decoration: none; display: flex; align-items: center; gap: 5px; box-shadow: 0 3px 10px rgba(0,0,0,0.2); z-index: 999; }

        footer { background: var(--dark); color: #fff; text-align: center; padding: 15px 10px; margin-top: 20px; font-size: 12px; }
        .footer-links { margin: 8px 0; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; font-size: 11px; }
        .footer-links a { color: #38bdf8; text-decoration: none; transition: 0.2s; }
        .footer-links a:hover { text-decoration: underline; color: #fff; }
        .footer-links span { color: #64748b; }
        .developer-credit { margin-top: 10px; font-size: 11px; color: #94a3b8; font-family: sans-serif; }
        .developer-credit strong, .developer-credit a { color: #38bdf8; text-decoration: none; }

        @media (min-width: 600px) {
            .nav-container { flex-direction: row; justify-content: space-between; }
            nav ul { width: auto; }
            .profile-card { flex-direction: row; text-align: right; }
        }
    </style>
</head>
<body>

    <div class="ticker-wrap">
        <div class="ticker-title">
            <span class="live-pulse"></span>
            <i class="fa-solid fa-bolt"></i> عاجل
        </div>
        <div class="ticker-content"><?php echo htmlspecialchars($ticker_text, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>

    <header>
        <div class="nav-container">
            <a href="index.php" class="brand">
                <img src="logo.jpg?v=<?php echo $cache_buster; ?>" alt="شعار حكي جالس" class="brand-logo-img">
                <div class="brand-text">
                    <h1>حكي جالس</h1>
                    <span>صفحة وموقع إخباري وسياسي</span>
                </div>
            </a>

            <div class="header-actions">
                <a href="<?php echo $fb_page_url; ?>" target="_blank" class="social-link-fb"><i class="fa-brands fa-facebook"></i> فيسبوك</a>
                <button class="notif-toggle" id="notif-btn" onclick="subscribeToNotifications()"><i class="fa-solid fa-bell"></i> الإشعارات</button>
                <button class="theme-toggle" onclick="toggleTheme()"><i class="fa-solid fa-moon"></i> الوضع الليلي</button>
            </div>

            <nav>
                <ul>
                    <li><button onclick="switchTab('home')" id="tab-home"><i class="fa-solid fa-house"></i> الرئيسية</button></li>
                    <li><button onclick="switchTab('about')" id="tab-about"><i class="fa-solid fa-user"></i> عن المنبر</button></li>
                    <li><button onclick="switchTab('videos')" id="tab-videos"><i class="fa-solid fa-video"></i> الفيديوهات</button></li>
                    <li><button onclick="switchTab('articles')" id="tab-articles"><i class="fa-solid fa-newspaper"></i> المقالات</button></li>
                    <li><button onclick="switchTab('bookmarks')" id="tab-bookmarks"><i class="fa-solid fa-bookmark"></i> المحفوظات</button></li>
                    <li><button onclick="switchTab('contact')" id="tab-contact"><i class="fa-solid fa-phone"></i> تواصل</button></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="main-wrapper">

        <div class="profile-card">
            <img src="father.jpg?v=<?php echo $cache_buster; ?>" alt="صاحب الموقع" class="profile-avatar">
            <div class="profile-info">
                <h2>صفحة وموقع "حكي جالس"</h2>
                <p style="font-size: 11px; color: #64748b;">منبر إعلامي وسياسي موجه لأهالي زحلة والبقاع</p>
                <div class="badges">
                    <span class="badge"><i class="fa-solid fa-location-dot"></i> زحلة - البقاع</span>
                    <span class="badge"><i class="fa-solid fa-tree"></i> ناشط بيئي وإجتماعي</span>
                    <span class="badge"><i class="fa-solid fa-microphone"></i> إعداد وتقديم: وليد الغزال</span>
                    <span class="badge"><i class="fa-solid fa-chart-line"></i> الزيارات: <?php echo number_format($total_visits); ?></span>
                </div>
            </div>
        </div>

        <div class="ad-banner-clean">
            <span class="ad-badge-clean"><i class="fa-solid fa-bullhorn"></i> إعلان</span>
            <div class="ad-title-clean">أصحاب المصالح والمؤسسات في زحلة والبقاع</div>
            <div class="ad-desc-clean">تريد إيصال خدماتك أو نشاطك لأكبر جمهور في المنطقة؟ انشر إعلانك معنا عبر منصة "حكي جالس".</div>
            <a href="https://wa.me/96170234131?text=أود%20الاستفسار%20عن%20الإعلانات%20في%20موقع%20حكي%20جالس" target="_blank" class="ad-btn-clean">
                <i class="fa-brands fa-whatsapp"></i> تواصل مع الإدارة للإعلان
            </a>
        </div>

        <section id="page-home" class="page-section">
            <h2 class="section-header">آخر الأخبار والمواقف</h2>
            <div class="grid-2">
                <div class="card">
                    <h3 style="color:var(--primary); font-size: 14px;"><i class="fa-solid fa-bullhorn" style="color:var(--accent);"></i> تصريح المنبر</h3>
                    <p style="margin-top:6px; line-height:1.5; font-size: 13px;">متابعة مستمرة للقضايا البيئية والاجتماعية في زحلة والبقاع، ونقل صوت المواطن بجرأة وشفافية.</p>
                </div>
                <div class="card">
                    <h3 style="color:var(--primary); font-size: 14px;"><i class="fa-solid fa-handshake" style="color:var(--accent);"></i> العمل الاجتماعي</h3>
                    <p style="margin-top:6px; line-height:1.5; font-size: 13px;">تفعيل دور الشباب في خدمة المجتمع والعمل على حل المشاكل البيئية المستجدة.</p>
                </div>
            </div>
        </section>

        <section id="page-about" class="page-section">
            <h2 class="section-header">عن المنبر والهيئة التحريرية</h2>
            <div class="card">
                <h3 style="color:var(--primary); font-size: 14px;">الرؤية والهدف</h3>
                <p style="margin-top: 6px; line-height: 1.6; font-size: 13px;">يعمل موقع "حكي جالس" كمنصة إعلامية وسياسية مستقلة تُعنى بنقل واقع وقضايا أهالي زحلة والبقاع.</p>
                <br>
                <h4 style="color:var(--primary); font-size: 13px;">الهيئة الإدارية والتحريرية:</h4>
                <ul style="margin-right: 15px; margin-top: 6px; font-size: 12px; line-height: 1.6;">
                    <li><strong>الإعلامي والمحاور الرئيسي:</strong> وليد الغزال</li>
                    <li><strong>التصوير والإخراج الفني:</strong> نسرين الغزال</li>
                    <li><strong>رئيس التحرير والناشر:</strong> إدارة موقع حكي جالس</li>
                    <li><strong>التطوير والدعم الفني:</strong> Eng. Elias Al Ghazal</li>
                </ul>
            </div>
        </section>

        <section id="page-videos" class="page-section">
            <h2 class="section-header">فيديوهات ولقاءات</h2>
            <div class="grid-2">
                <?php if ($videos_result && $videos_result->num_rows > 0): ?>
                    <?php while($row = $videos_result->fetch_assoc()): 
                        $video_url = $row['url'] ?? '#';
                        $embed_url = "";
                        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                            preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/を買]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $video_url, $match);
                            $youtube_id = $match[1] ?? '';
                            $embed_url = "https://www.youtube.com/embed/" . $youtube_id;
                        }
                    ?>
                        <div class="card">
                            <?php if (!empty($embed_url)): ?>
                                <div class="video-container">
                                    <iframe src="<?php echo $embed_url; ?>" allowfullscreen></iframe>
                                </div>
                            <?php endif; ?>
                            <h3 style="color:var(--primary); font-size: 14px;"><?php echo htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="share-btn-group">
                                <a href="<?php echo htmlspecialchars($video_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="share-btn share-fb"><i class="fa-solid fa-play"></i> فتح الفيديو الأصل</a>
                                <button onclick="nativeShare('<?php echo htmlspecialchars(addslashes($row['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $video_url; ?>')" class="share-btn share-native"><i class="fa-solid fa-share-nodes"></i> مشاركة</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="font-size: 12px;">لا يوجد فيديوهات منشورة حالياً.</p>
                <?php endif; ?>
            </div>
        </section>

        <section id="page-articles" class="page-section">
            <h2 class="section-header">المقالات والبيانات</h2>
            
            <div class="category-filter">
                <button class="cat-btn active" onclick="filterCategory('all', this)">الكل</button>
                <button class="cat-btn" onclick="filterCategory('سياسة', this)">سياسة</button>
                <button class="cat-btn" onclick="filterCategory('بيئة', this)">بيئة</button>
                <button class="cat-btn" onclick="filterCategory('زحلة والبقاع', this)">زحلة والبقاع</button>
            </div>

            <input type="text" id="articleSearch" onkeyup="filterArticles()" placeholder="🔍 ابحث في المقالات..." class="search-box">
            
            <div style="display:flex; flex-direction:column; gap:10px;" id="articlesList">
                <?php if ($articles_result && $articles_result->num_rows > 0): ?>
                    <?php $art_index = 0; while($row = $articles_result->fetch_assoc()): $art_index++; 
                        $art_id      = $row['id'] ?? 0;
                        $category    = $row['category'] ?? 'عام';
                        $content     = $row['content'] ?? '';
                        $title       = $row['title'] ?? '';
                        $read_time   = estimate_reading_time($content);
                        $likes_count = $row['likes_count'] ?? 0;
                    ?>
                        <div class="card article-item" data-category="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>" data-id="<?php echo $art_id; ?>">
                            <h3 class="article-title" style="color:var(--primary); font-size: 14px;"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h3>
                            
                            <div class="meta-info">
                                <span><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></span>
                                <span><i class="fa-solid fa-clock"></i> وقـت القراءة: <?php echo $read_time; ?> دقيقة</span>
                            </div>

                            <p id="art-text-<?php echo $art_index; ?>" class="article-content" style="margin-top:6px; line-height:1.5; font-size: 13px;">
                                <?php echo nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')); ?>
                            </p>
                            
                            <div class="share-btn-group">
                                <button onclick="readArticle('art-text-<?php echo $art_index; ?>')" class="share-btn btn-audio"><i class="fa-solid fa-volume-high"></i> استمع</button>
                                <button onclick="changeFontSize('art-text-<?php echo $art_index; ?>', 2)" class="share-btn btn-size"><i class="fa-solid fa-font"></i> A+</button>
                                <button onclick="toggleBookmark(<?php echo $art_id; ?>, '<?php echo addslashes(htmlspecialchars($title)); ?>')" class="share-btn btn-bookmark"><i class="fa-solid fa-bookmark"></i> حفظ</button>
                                <button onclick="nativeShare('<?php echo htmlspecialchars(addslashes($title), ENT_QUOTES, 'UTF-8'); ?>', window.location.href)" class="share-btn share-native"><i class="fa-solid fa-share-nodes"></i> مشاركة</button>
                            </div>

                            <div class="interactions-bar">
                                <button class="like-btn" onclick="likeArticle(<?php echo $art_id; ?>, this)">
                                    <i class="fa-solid fa-thumbs-up"></i> إعجاب (<span class="like-count"><?php echo $likes_count; ?></span>)
                                </button>
                            </div>

                            <div class="comments-section">
                                <input type="text" class="comment-input" placeholder="اكتب تعليقاً..." onkeypress="addComment(event, <?php echo $art_id; ?>, this)">
                                <div class="comments-list" id="comments-<?php echo $art_id; ?>"></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="font-size: 12px;">لا يوجد مقالات منشورة حالياً.</p>
                <?php endif; ?>
            </div>
        </section>

        <section id="page-bookmarks" class="page-section">
            <h2 class="section-header">المقالات المحفوظة</h2>
            <div id="bookmarksList" style="display:flex; flex-direction:column; gap:10px;"></div>
        </section>

        <section id="page-contact" class="page-section">
            <h2 class="section-header">معلومات التواصل وحق الرد</h2>
            <div class="card" style="font-size: 12px;">
                <p><i class="fa-solid fa-phone" style="color:var(--accent)"></i> <strong>رقم الهاتف / الواتساب:</strong> 70/234131</p>
                <br>
                <p><i class="fa-brands fa-facebook" style="color:#1877f2"></i> <strong>صفحتنا الرسمية:</strong> <a href="<?php echo $fb_page_url; ?>" target="_blank" style="color:var(--primary); text-decoration:none;">حكي جالس على الفيسبوك</a></p>
                <br>
                <p><i class="fa-solid fa-location-dot" style="color:var(--accent)"></i> <strong>العنوان:</strong> زحلة - البقاع، لبنان</p>
            </div>
        </section>

    </div>

    <a href="https://wa.me/96170234131?text=أود%20إرسال%20خبر/شكوى%20لموقع%20حكي%20جالس" target="_blank" class="float-wa">
        <i class="fa-brands fa-whatsapp" style="font-size: 15px;"></i> أرسل خبرك
    </a>

    <footer>
        <p>جميع الحقوق محفوظة &copy; صفحة وموقع حكي جالس</p>
        
        <div class="footer-links">
            <a href="javascript:void(0)" onclick="switchTab('about')">عن المنبر</a>
            <span>|</span>
            <a href="javascript:void(0)" onclick="switchTab('contact')">تواصل معنا</a>
            <span>|</span>
            <a href="javascript:void(0)" onclick="alert('موقع حكي جالس يلتزم بحفظ خصوصية بيانات الزوار وعدم مشاركتها مع أي طرف ثالث.')">سياسة الخصوصية</a>
        </div>

        <p class="developer-credit">
            Done by Engineer <strong>Elias Al Ghazal</strong> | For contact: <a href="mailto:eliasalghazal38@gmail.com">eliasalghazal38@gmail.com</a>
        </p>
    </footer>

    <script>
        function switchTab(pageId) {
            document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('nav button').forEach(b => b.classList.remove('active'));

            let activeSection = document.getElementById(`page-${pageId}`);
            let activeTabBtn  = document.getElementById(`tab-${pageId}`);

            if(activeSection && activeTabBtn){
                activeSection.classList.add('active');
                activeTabBtn.classList.add('active');
                if (window.history.pushState) {
                    window.history.pushState({tab: pageId}, "", "?tab=" + pageId);
                }
            }
            if(pageId === 'bookmarks') renderBookmarks();
        }

        function filterArticles() {
            let input = document.getElementById('articleSearch').value.toLowerCase();
            let articles = document.getElementsByClassName('article-item');
            
            for (let i = 0; i < articles.length; i++) {
                let title = articles[i].getElementsByClassName('article-title')[0].innerText;
                articles[i].style.display = title.toLowerCase().includes(input) ? "" : "none";
            }
        }

        function filterCategory(cat, btn) {
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            let articles = document.getElementsByClassName('article-item');
            for (let i = 0; i < articles.length; i++) {
                let itemCat = articles[i].getAttribute('data-category');
                articles[i].style.display = (cat === 'all' || itemCat === cat) ? "" : "none";
            }
        }

        function toggleTheme() {
            document.body.classList.toggle('dark-theme');
            localStorage.setItem('theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
        }

        if(localStorage.getItem('theme') === 'dark') document.body.classList.add('dark-theme');

        function subscribeToNotifications() {
            if (!('Notification' in window)) {
                alert('متصفحك لا يدعم نظام الإشعارات المباشرة.');
                return;
            }

            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    alert('تم تفعيل الإشعارات بنجاح! ستصلك أحدث التحديثات والأخبار فور نشرها.');
                    let btn = document.getElementById('notif-btn');
                    if(btn) {
                        btn.innerHTML = '<i class="fa-solid fa-bell-slash"></i> الإشعارات مفعلة';
                        btn.style.background = '#16a34a';
                        btn.style.color = '#fff';
                    }
                } else if (permission === 'denied') {
                    alert('تم تعطيل الإشعارات من إعدادات المتصفح.');
                }
            });
        }

        function nativeShare(title, url) {
            if (navigator.share) {
                navigator.share({ title: title, url: url });
            } else {
                navigator.clipboard.writeText(url);
                alert("تم نسخ الرابط بنجاح!");
            }
        }

        function toggleBookmark(id, title) {
            let bookmarks = JSON.parse(localStorage.getItem('bookmarks') || '[]');
            let index = bookmarks.findIndex(b => b.id === id);
            if(index === -1) {
                bookmarks.push({id: id, title: title});
                alert("تم إضافة المقال إلى المحفوظات!");
            } else {
                bookmarks.splice(index, 1);
                alert("تم إزالة المقال من المحفوظات!");
            }
            localStorage.setItem('bookmarks', JSON.stringify(bookmarks));
        }

        function renderBookmarks() {
            let bookmarks = JSON.parse(localStorage.getItem('bookmarks') || '[]');
            let container = document.getElementById('bookmarksList');
            if(bookmarks.length === 0) {
                container.innerHTML = "<p style='font-size:12px;'>لا يوجد مقالات محفوظة حالياً.</p>";
                return;
            }
            container.innerHTML = bookmarks.map(b => `
                <div class="card">
                    <h3 style="font-size:13px;">${b.title}</h3>
                    <button onclick="switchTab('articles')" class="share-btn btn-audio">قراءة المقال</button>
                </div>
            `).join('');
        }

        function likeArticle(articleId, btn) {
            fetch('api.php?action=like', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'article_id=' + articleId
            }).then(res => res.json()).then(data => {
                if(data.success) {
                    let countSpan = btn.querySelector('.like-count');
                    countSpan.innerText = parseInt(countSpan.innerText) + 1;
                    btn.classList.add('liked');
                }
            }).catch(e => console.log('API call pending'));
        }

        function addComment(e, articleId, input) {
            if(e.key === 'Enter' && input.value.trim() !== '') {
                let commentText = input.value.trim();
                fetch('api.php?action=comment', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `article_id=${articleId}&comment=${encodeURIComponent(commentText)}`
                }).then(res => res.json()).then(data => {
                    if(data.success) {
                        let list = document.getElementById(`comments-${articleId}`);
                        list.innerHTML += `<div class="comment-item">💬 ${commentText}</div>`;
                        input.value = '';
                    }
                }).catch(e => console.log('API call pending'));
            }
        }

        function readArticle(elementId) {
            if ('speechSynthesis' in window) {
                if (window.speechSynthesis.speaking) {
                    window.speechSynthesis.cancel();
                    return;
                }
                let text = document.getElementById(elementId).innerText;
                let currentSpeech = new SpeechSynthesisUtterance(text);
                currentSpeech.lang = 'ar-SA';
                window.speechSynthesis.speak(currentSpeech);
            }
        }

        function changeFontSize(elementId, step) {
            let el = document.getElementById(elementId);
            if(el) {
                let currentSize = parseFloat(window.getComputedStyle(el, null).getPropertyValue('font-size'));
                el.style.fontSize = (currentSize + step) + 'px';
            }
        }

        switchTab('<?php echo $active_tab; ?>');
    </script>
</body>
</html>