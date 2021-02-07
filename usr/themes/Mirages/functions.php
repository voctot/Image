<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

require_once("lib/Const.php");
require_once("lib/Settings.php");
require_once("lib/I18n.php");
require_once("lib/Mirages.php");
require_once("lib/Title.php");
require_once("lib/CollapseTitle.php");
require_once("lib/Content.php");
require_once("lib/Utils.php");
require_once("lib/Device.php");
require_once("lib/TOC.php");
require_once("lib/Lang.php");
require_once("lib/Comments.php");
require_once("lib/CategoryList.php");
require_once("usr/License.php");

function themeInit(Widget_Archive $archive) {
    Mirages::init();
    $options = Mirages::$options;
    if ($options->miragesInited) {
        return;
    }
    define("STATIC_VERSION", $options->devMode == 1 ? (Mirages::$version . "." . time()) : Mirages::$version);
    define("USE_EMBED_FONTS", $options->webFont == 0);
    define("USE_GOOGLE_FONTS", $options->webFont == 1);
    define("USE_SERIF_FONTS", $options->enableSerifFonts || @$_COOKIE['MIRAGES_USE_SERIF_FONTS'] == 1);

    if (strtoupper($options->language) != "AUTO") {
        I18n::setLang($options->language);
    }

    define('THEME_MIRAGES_ROOT_DIR', rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
    define("TEST_STATIC_PATH", rtrim(preg_replace('/^'.preg_quote(rtrim($options->siteUrl, '/'), '/').'/', rtrim($options->rootUrl, '/'), $options->themeUrl, 1),'/').'/');
    if (strlen(trim($options->cdnDomain)) > 0 && $options->devMode__isFalse) {
        define("STATIC_PATH", rtrim(preg_replace('/^'.preg_quote(rtrim($options->siteUrl, '/'), '/').'/', rtrim($options->cdnDomain, '/'), $options->themeUrl, 1),'/').'/');
        $options->cdnEnabled = TRUE;
    } else {
        define("STATIC_PATH", TEST_STATIC_PATH);
        $options->cdnEnabled = FALSE;
    }

    define("LIGHT_THEME_CLASS", "theme-white");
    @$rootFontSize = $_COOKIE['MIRAGES_ROOT_FONT_SIZE'];
    $rootFontSize = intval($rootFontSize);
    if ($rootFontSize < 80 || $rootFontSize > 200) {
        $rootFontSize = 100;
    }
    $options->rootFontSize = $rootFontSize;
    if ($rootFontSize !== 100) {
        $options->rootFontSizeStyle = "style=\"font-size: {$rootFontSize}%;\"";
    } else {
        $options->rootFontSizeStyle = "";
    }

    @$nightShift = $_COOKIE['MIRAGES_NIGHT_SHIFT_MODE'];

    if (!$options->defaultTheme__hasValue) {
        $options->defaultTheme = Mirages_Const::THEME_MIRAGES_AUTO;
    }
    if (empty($nightShift)) {
        if ($options->defaultTheme == Mirages_Const::THEME_MIRAGES_WHITE) {
            $nightShift = 'DAY';
        } elseif ($options->defaultTheme == Mirages_Const::THEME_MIRAGES_SUNSET) {
            $nightShift = 'SUNSET';
        } elseif ($options->defaultTheme == Mirages_Const::THEME_MIRAGES_DARK) {
            $nightShift = 'NIGHT';
        } else {
            $nightShift = 'AUTO';
        }
    }
    $nightShift = strtoupper($nightShift);
    if ($nightShift == "NIGHT") {
        $themeClass = "theme-dark dark-mode";
        $nightShiftBtnClass = "night-mode";
    } elseif ($nightShift == "DAY") {
        $themeClass = LIGHT_THEME_CLASS;
        $nightShiftBtnClass = "day-mode";
    } elseif ($nightShift == "SUNSET") {
        $themeClass = LIGHT_THEME_CLASS . " theme-sunset";
        $nightShiftBtnClass = "sunset-mode";
    } else {
        $themeClass = LIGHT_THEME_CLASS;
        $nightShiftBtnClass = "auto-mode";
    }


    define("THEME_CLASS", $themeClass);
    define("NIGHT_SHIFT_BTN_CLASS", $nightShiftBtnClass);

    if ($options->rewrite == 0) {
        define("REWRITE_FIX", "index.php/");
    } else {
        define("REWRITE_FIX", "");
    }
    define('IS_HTTPS', @$_SERVER['HTTPS']);

    if (Utils::hasValue($options->disqusShortName)) {
        define("COMMENT_SYSTEM", Mirages_Const::COMMENT_SYSTEM_DISQUS);
    } else {
        define("COMMENT_SYSTEM", Mirages_Const::COMMENT_SYSTEM_EMBED);
    }

    define("PJAX_ENABLED", $options->enablePjax == 1 && !Device::isIE() && !Device::isSpider() && COMMENT_SYSTEM !== Mirages_Const::COMMENT_SYSTEM_GENTIE);

    $options->miragesInited = true;
}

function themeConfig(Typecho_Widget_Helper_Form $form) {

    I18n::loadAsSettingsPage(true);
    echo Mirages::welcome();


    $form->addInput(new CollapseTitle('domainToken', array("open"), NULL, _mt('主题授权校验<span style="color: #f2777a; margin-left: 5px; font-size: 2rem; line-height: 1rem;vertical-align: bottom">*</span>'), NULL));
    $domainKey = new Typecho_Widget_Helper_Form_Element_Text('k', NULL, NULL, _mt('域名授权 Key'),_mt('域名校验 Key 可以到 <a href="https://store.get233.com/" target="_blank">主题小商店</a> 中我的订单处登记您的域名后获取。<br>如有任何疑问请直接联系作者。'));
    $form->addInput($domainKey);

    $form->addInput(new CollapseTitle('appearanceTitle', NULL, NULL, _mt('主题外观'), NULL));
    $baseTheme = new Typecho_Widget_Helper_Form_Element_Select('defaultTheme', array('1'=>_mt('自动'), '2'=>_mt('浅色主题'), '3'=>_mt('日落主题'), '4'=>_mt('深色主题')), '1', _mt('默认主题'),_mt('设置默认展示的主题样式。用户可以在浏览器端的主题颜色设置对其进行覆盖。另外，您可以通过高级设置禁用部分浏览器端的可选主题样式。<br>自动：根据浏览器时间或用户操作系统外观选项自动切换到深色主题；其他：与其名称对应颜色的主题'));
    $form->addInput($baseTheme);
//    $baseTheme = new Typecho_Widget_Helper_Form_Element_Select('baseTheme', array('1'=>_mt('Mirages White'), '2'=>_mt('Mirages Dark'), '0'=>_mt('Mirages')), '1', _mt('主题基础色调'),_mt('默认为 Mirages White'));
//    $form->addInput($baseTheme);
//    $disableAutoNightTheme = new Typecho_Widget_Helper_Form_Element_Select('disableAutoNightTheme', array('0'=>_mt('开启'), '1'=>_mt('关闭')), '0', _mt('自动夜间模式'),_mt('默认为开启'));
//    $form->addInput($disableAutoNightTheme);
    $useCardView = new Typecho_Widget_Helper_Form_Element_Select('useCardView', array('0'=>_mt('不使用'), '1'=>_mt('使用')), '1', _mt('使用卡片式文章列表'),_mt('换一种风格，此模式对配图的要求较高'));
    $form->addInput($useCardView);
//    $showBannerCurveStyle = new Typecho_Widget_Helper_Form_Element_Select('showBannerCurveStyle', array('0'=>_mt('否'), '1'=>_mt('是')), '0', _mt('Banner 下边界添加弧型遮罩'),_mt('默认不添加弧型遮罩'));
//    $form->addInput($showBannerCurveStyle);
    $language = new Typecho_Widget_Helper_Form_Element_Select('language', I18n_Options::listLangs(), 'auto',
        _mt('界面语言'), _mt('默认为自动, 即根据浏览器设置自动选择语言。'));
    $form->addInput($language->multiMode());

    $form->addInput(new CollapseTitle('noticeTitle', NULL, NULL, _mt('网站公告'), NULL));
    $blogNotice = new Typecho_Widget_Helper_Form_Element_Textarea('blogNotice', NULL, NULL, _mt('博客公告消息'), _mt('显示在博客页面顶端的一条消息。'));
    $form->addInput($blogNotice);
    $blogIntro = new Typecho_Widget_Helper_Form_Element_Text('blogIntro', NULL, NULL, _mt('首页大图内文字'), _mt('显示在博客首页大图内的文字。'));
    $form->addInput($blogIntro);
    $blogIntroDesc = new Typecho_Widget_Helper_Form_Element_Text('blogIntroDesc', NULL, NULL, _mt('首页大图内描述'), _mt('显示在博客首页大图内的描述。'));
    $form->addInput($blogIntroDesc);


    $form->addInput(new CollapseTitle('imageTitle', NULL, NULL, _mt('配图及图像管理'), '在设置背景图的时候，例如站点背景大图地址、默认背景图列表、文章主图的时候，设置方式均为每行填写一张图片，如果设置了多张图片，则主题会随机挑选一张进行展示。<br>对于单张图片设置，您需要填写图片的完整 URL，对于展示重点不在中心的图片，您可以通过在图片 URL 前添加定位参数。<br>示例: ' . Utils::toCode('[center,bottom]https://example.com/image.jpg') . '<br> 其中，中括号内逗号前的参数为水平定位，逗号后的参数为垂直定位。<br>可用的定位参数有: ' . Utils::toCode('top, center, bottom') . '<br>示例中的定位即为水平居中，垂直方向有限展示图片底部。不设置定位参数时，水平及垂直方向均为居中定位。<br>注: '. Utils::toCode('{{MIRAGES_ROOT}}') . '的作用为调用主题内部图片，不需要关注这个。<br><br>'));
    $defaultBg = new Typecho_Widget_Helper_Form_Element_Textarea('defaultBg', NULL, '[center,bottom]{{MIRAGES_ROOT}}/images/default/katie-treadway-EwE4tBYh3ms-unsplash.jpg', _mt('站点背景大图地址'), _mt('在这里填入图片的URL地址, 以在网站首页显示一个背景大图。每行填写一个链接（自动换行的视为同一行），多行则随机选一张进行展示，留空则不显示。'));
    $form->addInput($defaultBg);
    $defaultBgHeight = new Typecho_Widget_Helper_Form_Element_Hidden('defaultBgHeight', NULL, '55', _mt('站点背景大图高度(%)'), _mt('站点背景大图高度占屏幕总高度的百分比。'));
    $defaultBgHeight->input->setAttribute('class', 'mini');
    $form->addInput($defaultBgHeight);

    $defaultMobileBgHeight = new Typecho_Widget_Helper_Form_Element_Hidden('defaultMobileBgHeight', NULL, '40', _mt('站点背景大图高度(竖屏下)(%)'), _mt('站点背景大图高度占屏幕总高度的百分比。'));
    $defaultMobileBgHeight->input->setAttribute('class', 'mini');
    $form->addInput($defaultMobileBgHeight);

    $headTitle = new Typecho_Widget_Helper_Form_Element_Hidden('headTitle', array('1'=>_mt('开启'), '0'=>_mt('关闭')), '1', _mt('标题默认显示在文章主图中'),_mt('标题默认显示在文章主图中，没有文章主图会显示默认背景色。<br>之前作为自定义字段「headTitle」存在的，现在提出作为一个全局设置。<br>你仍然可以对某篇文章进行特殊设置。'));
    $form->addInput($headTitle);
    $categoryBanner = new Typecho_Widget_Helper_Form_Element_Textarea('categoryBanner', NULL, '', _mt('分类背景大图设置').' <a href="https://get233.com/archives/category-banner.html" target="_blank"><span style="">'._mt('配置文档').'</span></a>', NULL);
    $form->addInput($categoryBanner);
    $defaultThumbnails = new Typecho_Widget_Helper_Form_Element_Textarea('defaultThumbnails', NULL, '[center,top]{{MIRAGES_ROOT}}/images/default/katie-treadway-EwE4tBYh3ms-unsplash.jpg', _mt('卡片式文章列表的默认背景图列表'), _mt('在这里填入图片的URL地址, 每行填写一个链接（自动换行的视为同一行），在文章没有配置文章主图的时候，将会从这里挑选一张图片进行显示。'));
    $form->addInput($defaultThumbnails);
    $sideMenuAvatar = new Typecho_Widget_Helper_Form_Element_Text('sideMenuAvatar', NULL, NULL, _mt('个人头像'), _mt('个人头像将显示在侧边导航栏及关于页面中，默认使用 Gravatar 头像。'));
    $form->addInput($sideMenuAvatar);

    $form->addInput(new CollapseTitle('colorTitle', NULL, NULL, _mt('自定义主色调'), NULL));
    $themeColor = new Typecho_Widget_Helper_Form_Element_Text('themeColor', NULL, NULL, _mt('自定义主题主色调'), _mt('默认为<span style="color: #1abc9c;">#1abc9c</span>, 你可以自定义任何你喜欢的颜色作为主题主色调。自定义主色调必须使用 Hex Color, 即`#233333`或`#333`的格式。填写错误的格式可能不会生效。'));
    $themeColor->input->setAttribute('class', 'mini');
    $form->addInput($themeColor);
    $themeColorDark = new Typecho_Widget_Helper_Form_Element_Text('themeColorDark', NULL, NULL, _mt('自定义主题主色调(夜间模式)'), _mt('如果不定义，则使用非夜间模式下定义的颜色, 你可以自定义任何你喜欢的颜色作为主题主色调。自定义主色调必须使用 Hex Color, 即`#233333`或`#333`的格式。填写错误的格式可能不会生效。'));
    $themeColorDark->input->setAttribute('class', 'mini');
    $form->addInput($themeColorDark);
    $themeSelectionColor = new Typecho_Widget_Helper_Form_Element_Hidden('themeSelectionColor', NULL, '#fff', _mt('自定义 Selection Color'), _mt('使用鼠标选中文字时文字的颜色, 默认为白色, 你可以自定义任何你喜欢的颜色, 但必须使用 Hex Color, 即`#233333`或`#333`的格式。填写错误的格式可能不会生效。<br>建议根据颜色色调使用黑色或白色。'));
    $themeSelectionColor->input->setAttribute('class', 'mini');
    $form->addInput($themeSelectionColor);
    $themeSelectionBackgroundColor = new Typecho_Widget_Helper_Form_Element_Hidden('themeSelectionBackgroundColor', NULL, NULL, _mt('自定义 Selection Background Color'), _mt('使用鼠标选中文字时文字、图片等元素的背景颜色, 默认(此项不填)和自定义主题主色调相同, 你可以自定义任何你喜欢的颜色, 但自定义主色调必须使用 Hex Color, 即`#233333`或`#333`的格式。填写错误的格式可能不会生效。'));
    $themeSelectionBackgroundColor->input->setAttribute('class', 'mini');
    $form->addInput($themeSelectionBackgroundColor);


//    $form->addInput(new CollapseTitle('languageTitle', NULL, NULL, _mt('界面语言'), NULL));


//    $form->addInput(new CollapseTitle('postTitle', NULL, NULL, _mt('文章与阅读'), NULL));
    $partOutput = new Typecho_Widget_Helper_Form_Element_Hidden('partOutput', array('0'=>_mt('朕知道了')), NULL, _mt('文章列表不输出全文预览'),_mt('目前主题首页及其他文章列表中使用带格式的文章输出, 而不是将正文文本去格式后截断。<br>如果你只想输出部分文章, 那么你可以在需要截断的地方使用 '.Utils::toCode('&lt!--more--&gt').' 标签截断文章。<br>编辑器工具区有「摘要分割线」按键, 可以在你当前编辑区域光标所在位置插入上述标签。'));
    $form->addInput($partOutput);
    $TOCDisplayMode = new Typecho_Widget_Helper_Form_Element_Hidden('TOCDisplayMode', array('1'=>_mt('手动启用，默认隐藏'), '2'=>_mt('手动启用，默认显示在右侧'), '3'=>_mt('手动启用，默认显示在左侧'), '4'=>_mt('手动启用，始终显示在右侧，不可隐藏'), '5'=>_mt('手动启用，始终显示在左侧，不可隐藏')), '1', _mt('文章目录树显示模式'), _mt('手动启用: 意思是需要到文章中打开相关选项，该选项可以使目录树在启用选项的文章中显示；目前没有统一的打开所有文章目录树的设置。<br>默认隐藏: 打开包含目录树的页面后不会默认展开目录树，需要用户点击按钮后展开。<br>默认显示在左/右侧: 用户打开包含目录树的文章页时目录树会自动展开。<br>不可隐藏: 将隐藏展开/关闭目录树的按钮，即目录树不可以被关闭。'));
    $form->addInput($TOCDisplayMode);
    $enableSerifFonts = new Typecho_Widget_Helper_Form_Element_Hidden('enableSerifFonts', array('0'=>_mt('使用无衬线字体 (黑体)'), '1'=>_mt('使用衬线字体 (通过 WebFont 使用思源宋体)')), '0', _mt('字体风格选择'), _mt('在这里，你可以简单的把<a href="https://zh.wikipedia.org/wiki/衬线体" target="_blank">衬线体</a>理解为宋体，<a href="https://zh.wikipedia.org/wiki/无衬线体" target="_blank">无衬线体</a>理解为黑体，如微软雅黑、苹方等。具体的区别可以参考百科。<br>在本主题中，二者的优劣如下：<br>无衬线体使用的范围较广，但在大篇幅文字的情况下阅读体验并不如衬线体。<br>衬线体更适合阅读，但适合的文章类型有限，并不适合带有代码的文章，适合的类型为大篇幅的文字，附带少量配图的文章，如小说、叙事、甚至作文等。衬线体对屏幕和渲染引擎的要求较高，但经测试，在低分屏 Windows 的主流浏览器（Chrome）上效果可以接受。另外，由于主题通过 webFont 的方式引入字体，所以启用衬线体后页面的加载流量会更大些（一篇文章大概多 200KB 左右），且因网速的限制，在 webFont加载完成前会使用回退字体（除了 macOS，几乎其他系统都会会退到黑体，Windows 本人手动回退到了微软雅黑，默认宋体不能忍），在加载完成后，会有字体变更的问题，体验一般。<br>另外，由于中文 WebFont 字体实在太大，所以要启用衬线体，需要使用 Google Fonts 字体服务<br>说了这么多，如果你经常要放置代码，那我并不推荐你使用衬线体，请使用无衬线体选项，另外如果你见宋体就想打人。。。'));
    $form->addInput($enableSerifFonts);
    $contentLang = new Typecho_Widget_Helper_Form_Element_Hidden('contentLang', array('zh'=>_mt('默认(中文)'), 'en'=>_mt('英语'), 'en_serif'=>_mt('英语(衬线体)')), 'zh', _mt('博客文章语言'), _mt('针对特定语言的排版优化。如果为英文博客，则请将此选项设置为英语，否则请保持默认。'));
    $form->addInput($contentLang);


    $form->addInput(new CollapseTitle('sidebarTitle', NULL, NULL, _mt('导航栏及侧边导航栏'), _mt('<a href="https://get233.com/archives/mirages-standalone-page.html" target="_blank">导航栏侧边栏菜单及固定页面（友链、关于、归档等）的相关文档</a><br>导航栏样式使用顶部导航条时，主题将在电脑及平板上展示位于屏幕顶部的横向导航条，在手机上展示侧边导航栏（默认隐藏在屏幕左侧）。导航栏样式使用侧边导航栏时，所有设备都仅展示侧边导航栏（默认隐藏在屏幕左侧）')));
    $navbarStyle = new Typecho_Widget_Helper_Form_Element_Select('navbarStyle', array('0'=>_mt('使用左侧侧边栏'), '1'=>_mt('使用顶部导航条')), '1', _mt('导航栏样式'),NULL);
    $form->addInput($navbarStyle);
    $navbarLogo = new Typecho_Widget_Helper_Form_Element_Text('navbarLogo', NULL, NULL, _mt('顶部导航栏 - 网站 Logo / 站点名称'), _mt('配置网站的 Logo，<strong>该选项仅作用于顶部导航条</strong><br>这里可以填写图片 URL（建议使用 PNG 格式），或填写网站名称（建议不超过 5 个字）'));
    $form->addInput($navbarLogo);
    $alwaysShowDashboardInSideMenu = new Typecho_Widget_Helper_Form_Element_Hidden('alwaysShowDashboardInSideMenu', array('0'=>_mt('否'), '1'=>_mt('是')), '0', _mt('始终显示 Dashboard(控制台) 菜单'),_mt('默认情况下，在你后台(Admin)保持登录状态时，将会在侧边栏显示「Dashboard」菜单可以快速进入后台。<br>勾选此选项后将始终显示此菜单项，未登录时将跳转到登录页面'));
    $form->addInput($alwaysShowDashboardInSideMenu);
    $toolbarItems = new Typecho_Widget_Helper_Form_Element_Textarea('toolbarItems', NULL, NULL, _mt('导航栏操作按钮').' <a href="https://get233.com/archives/toolbar.html" target="_blank"><span style="">'._mt('配置文档').'</span></a>', NULL);
    $form->addInput($toolbarItems);


//    $form->addInput(new CollapseTitle('commentsEmbedTitle', NULL, NULL, _mt('评论 - 自带'), NULL));
    $defaultGravatar = new Typecho_Widget_Helper_Form_Element_Hidden('defaultGravatar', NULL, NULL, _mt('默认 Gravatar 头像'), _mt(''));
    $form->addInput($defaultGravatar);
//    $embedCommentOptions = new Typecho_Widget_Helper_Form_Element_Hidden('embedCommentOptions',
//        array(
//            'disableQQAvatar' => _mt('不加载评论者的 QQ 头像'),
//            'comment2ViewStrict' => _mt('「评论回复可见」要求评论必须为通过状态（评论待审核或垃圾评论等不会使隐藏内容可见）'),
//        ),
//        array(), _mt('其他选项'), _mt('该功能依赖 Mirages 插件。'.Mirages::pluginAvailableMessage(106, '1.0.6')));
//    $form->addInput($embedCommentOptions->multiMode());


//    $form->addInput(new CollapseTitle('commentsDisqusTitle', NULL, NULL, _mt('评论 - Disqus'), ' <a href="https://get233.com/archives/mirages-disqus.html" target="_blank">'._mt('Disqus 评论及相关配置文档').'</a>'));
    $disqusShortName = new Typecho_Widget_Helper_Form_Element_Hidden('disqusShortName', NULL, NULL, _mt('Disqus Short Name'), _mt('Disqus 评论'));
    $disqusShortName->input->setAttribute('class', 'mini');
    $form->addInput($disqusShortName);



    $form->addInput(new CollapseTitle('qrcodeTitle', NULL, NULL, _mt('二维码及打赏'), NULL));
    $postQRCodeURL = new Typecho_Widget_Helper_Form_Element_Text('postQRCodeURL', NULL, NULL, _mt('本页二维码生成地址'), _mt("用于「扫喵二维码在手机上继续访问」，使用占位符表示文章链接。留空则不显示。支持的占位符有: <br>"
        .Utils::toCode('{{%LINK}}').": 当前页链接<br>"
        .Utils::toCode('{{%BASE64_LINK}}').": Base64后的当前页链接<br>"
        .Utils::toCode('{{%BASE64_LINK_WITHOUT_SLASH}}').": Base64后的当前页链接, 使用`-`替换`/`。<br>"));
    $form->addInput($postQRCodeURL);
    $rewardQRCodeURL = new Typecho_Widget_Helper_Form_Element_Text('rewardQRCodeURL', NULL, NULL, _mt('打赏二维码图片地址'), _mt("打赏二维码图片地址, 只支持放一张图片, 请用 PS 等软件拼合多张二维码。留空则不显示。"));
    $form->addInput($rewardQRCodeURL);



    $form->addInput(new CollapseTitle('speedTitle', NULL, NULL, _mt('速度优化'), _mt('<strong>在配置及使用此章节的内容前，建议先阅读该篇文章</strong>：<a href="https://get233.com/archives/mirages-cdn-optimization.html" target="_blank">说说主题的云存储优化功能</a>')));
    $cdnDomain = new Typecho_Widget_Helper_Form_Element_Text('cdnDomain', NULL, NULL, _mt('CDN 镜像加速域名'), _mt('用于加速网站静态资源和图片等内容。<br>在这里填入你的<span style="color: #f2777a">镜像加速域名</span>, 如七牛加速域名等。<br><span style="color: #f2777a">但在此之前，你必须先将加速域名相应的镜像存储的镜像源设置为你当前的博客域名。</span><br>举个例子，将七牛镜像存储的镜像源设置为'.Utils::toCode('https://get233.com/').',然后在这里填入七牛的空间对应的域名如'.Utils::toCode('https://your-qiniu.qnssl.com/').'即可。'.Mirages::pluginAvailableMessage(101, '1.0.1')));
    $form->addInput($cdnDomain);
    $qiniuOptionsBlock = new Typecho_Widget_Helper_Form_Element_Checkbox('qiniuOptions',
        array(
            'qiniuOptimize' => _mt('云存储优化支持（要启用下面的选项，必须先启用本项）'),
            'useQiniuImageResize' => _mt('为文章中的图片自动转换合适的大小和格式'),
            'enableWebP' => _mt('启用 WebP 图像格式'),
        ),
        array(), _mt('云存储优化'), _mt('目前支持的云存储服务有：七牛云存储、又拍云存储、阿里云 OSS及腾讯云存储（需要开启<a href="https://cloud.tencent.com/product/ci" target="_blank">数据万象</a>功能），并依赖 Mirages 插件。'.Mirages::pluginAvailableMessage(103, '1.0.3')));
    $form->addInput($qiniuOptionsBlock->multiMode());
    $enableLazyLoad = new Typecho_Widget_Helper_Form_Element_Select('enableLazyLoad', array('0'=>_mt('关闭'), '1'=>_mt('开启')), '0', _mt('图像加载动画'),_mt('默认为关闭。<br>图像加载时的渐变和模糊效果，当前仅支持七牛云存储、又拍云存储、阿里云 OSS 及腾讯云存储（需要开启<a href="https://cloud.tencent.com/product/ci" target="_blank">数据万象</a>功能）'.Mirages::pluginAvailableMessage(103, '1.0.3')));
    $form->addInput($enableLazyLoad);
    $dnsPrefetch = new Typecho_Widget_Helper_Form_Element_Textarea('dnsPrefetch', NULL, NULL, _mt('DNS Prefetch'), _mt('DNS 预读取是一种使浏览器主动执行 DNS 解析已达到优化加载速度的功能。<br>你可以在这里设置需要预读取的域名，<bold>每行一个，仅填写域名即可。</bold><br>如：' . Utils::toCode('img.example.com')));
    $form->addInput($dnsPrefetch);


    $form->addInput(new CollapseTitle('extendsTitle', NULL, NULL, _mt('编辑器及 Markdown 扩展'), NULL));
    $enableVditor = new Typecho_Widget_Helper_Form_Element_Select('enableVditor', array('0'=>_mt('不启用'), '1'=>_mt('启用 Vditor')), '0', _mt('启用即时渲染编辑器 Vditor (BETA)'),_mt('<a href="https://b3log.org/vditor/" target="_blank">Vditor</a> 是一款浏览器端的 Markdown 编辑器，支持类似 Typora 的即时渲染、分屏预览等模式。<br>官方 Demo: <a href="https://ld246.com/guide/markdown" target="_blank">https://ld246.com/guide/markdown</a><br>当前主题集成 Vditor 编辑器仍处于测试阶段，可能有部分功能缺失及不稳定的情况发生。<br><span style="color: #f2777a; font-weight: bold">启用该选项前请确保 <code>控制台 -> 个人设置 -> 撰写设置 -> 使用 Markdown 语法编辑和解析内容</code> 选项为打开</span>'));
    $form->addInput($enableVditor);
    $vditorDefaultMode = new Typecho_Widget_Helper_Form_Element_Select('vditorDefaultMode', array('1'=>_mt('所见即所得'), '2'=>_mt('即时渲染'), '3'=>_mt('分屏预览'), '4'=>_mt('单编辑器')), '1', _mt('Vditor 默认编辑模式'),_mt('Vditor 目前支持三种编辑模式：所见即所得、即时渲染、分屏预览。分屏预览分为编辑器与预览页面各占一半的视图及纯编辑器的视图，分别对应下拉框中的四个选项。具体的可以参考 <a href="https://ld246.com/guide/markdown" target="_blank">官方 Demo</a><br><strong>所见即所得：</strong>如名称所示，比较适合<strong>不熟悉</strong> Markdown 的同学。<br><strong>即时渲染：</strong>类 <a href="https://typora.io/" target="_blank">Typora</a> 的编辑模式，比较适合对 Markdown 有一定了解的同学。<br><strong>分屏预览：</strong>常规 Markdown 编辑模式，编辑器及预览各占一半视图<br><strong>单编辑器：</strong>常规 Markdown 编辑模式，仅展示编辑器，不展示预览。<br>编辑页面工具栏的：<code>编辑模式</code>按钮可以很方便的在各种模式之前切换；<code>更多 -> 编辑 & 预览</code>可以在分屏预览及单编辑器模式中互相切换<p> </p><hr>'));
    $form->addInput($vditorDefaultMode);
    $markdownExtendBlock = new Typecho_Widget_Helper_Form_Element_Checkbox('markdownExtend',
        array(
            'enablePhonetic' => _mt("添加 ".Utils::toCode('{{拼音 : pin yin}}')." 语法解析注音"),
            'enableDeleteLine' => _mt("添加 ".Utils::toCode('~~要加删除线的内容~~')." 语法解析删除线, 你可以在必要的时候使用 ".Utils::toCode('\\~')." 转义以输出字符 ".Utils::toCode('~').""),
            'enableHighlightText' => _mt("添加 ".Utils::toCode('==要高亮显示的内容==')." 语法解析高亮 (荧光笔效果), 你可以在必要的时候使用 ".Utils::toCode('\\\\=')." 转义以输出字符 ".Utils::toCode('=').". <strong>该功能暂不支持高亮行内代码</strong>"),
            'enableCheckbox' => _mt("添加 ".Utils::toCode('- [ ] 未选中')."和" .Utils::toCode('- [x] 选中'). " 语法解析带复选框的列表(复选框仅做展示，页面不可修改)"),
        ),
        array(), _mt('Markdown 语法扩展').Mirages::pluginAvailableMessage(104, '1.0.4'), _mt('Markdown 语法扩展扩展示例: <a href="https://get233.com/archives/mirages-markdown-extension-example.html" target="_blank">Markdown 语法扩展示例</a>'));
    $form->addInput($markdownExtendBlock->multiMode());
    $texOptionsBlock = new Typecho_Widget_Helper_Form_Element_Checkbox('texOptions',
        array(
            'showJax' => _mt('显示数学公式 (MathJax)'),
            'useDollarForInline' => _mt("使用 ".Utils::toCode('$ ... $')." 输入行内公式"),
        ),
        array(), _mt('数学公式支持'), _mt("<strong>在这里启用的选项对所有文章生效。你也可以选择不启用此处的设置，然后在需要启用的文章中对单篇文章启用。</strong><br>在启用「显示数学公式」后, 你可以使用 ".Utils::toCode('$$ ... $$')." 或 ".Utils::toCode('\\[ ... \\]')." 输入块级公式（未启用插件则需使用".Utils::toCode('\\\\[ ... \\\\]')."）, 使用 ".Utils::toCode('\\( ... \\)')." 输入行内公式（未启用插件则需使用".Utils::toCode('\\\\( ... \\\\)')."）。<br>".
            "在启用了「使用 ".Utils::toCode('$ ... $')." 输入行内公式」选项后, 你可以使用 ".Utils::toCode('$ ... $')." 来输入行内公式, 但因为 ".Utils::toCode('$')." 符出现的可能比较频繁, 因此可能会造成误判的情况。<br>".
            "如: ".Utils::toCode('... the cost is $2.50 for the first one, and $2.00 for each additional one ...')."<br>".
            "将会对 ".Utils::toCode('2.50 for the first one, and ')." 进行解析。<br>".
            "当然, 你也可以使用转义符对 ".Utils::toCode('$ ... $')." 进行转义, 如 ".Utils::toCode('\\$')."<br><br>".
            "<strong>由于完整的 MathJax 体积非常大，且绝大部分用户用不到所有的 MathJax 功能，所以主题仅附带了部分文件。若您在使用的过程中出现了部分公式无法解析的情况，则可以前往 <a href='https://github.com/mathjax/MathJax/releases/tag/2.7.5' target='_blank'>Github (版本: 2.7.5)</a> 下载完整的文件，然后解压后，将其中的文件替换至 ".Utils::toCode('/usr/themes/Mirages/static/mathjax/2.7.5/')." 目录中即可。</strong>"));
    $form->addInput($texOptionsBlock->multiMode());
    $flowChartBlock = new Typecho_Widget_Helper_Form_Element_Checkbox('flowChartOptions',
        array(
            'showFlowChart' => _mt("启用流程图支持")
        ),
        array(), _mt('流程图支持'), _mt("在启用流程图支持后，您可以通过 Markdown 的流程图语法(非原生支持)来创建流程图。<br><strong>在这里启用的选项对所有文章生效。你也可以选择不启用此处的设置，然后在需要启用的文章中对单篇文章启用。</strong><br>主题使用的渲染流程图的工具为 <a href=\"https://github.com/adrai/flowchart.js\" target=\"_blank\">flowchart.js</a><br>流程图语法可以参考: <a href=\"https://segmentfault.com/a/1190000006247465\" target=\"_blank\">Markdown 流程图语法参考</a>"));
    $form->addInput($flowChartBlock->multiMode());
    $mermaidBlock = new Typecho_Widget_Helper_Form_Element_Checkbox('mermaidOptions',
        array(
            'showMermaid' => _mt("启用 Mermaid 支持")
        ),
        array(), _mt('Mermaid 支持'), _mt("在启用 Mermaid 支持后，您可以通过 Mermaid 的语法(非原生支持)来创建流程图、时序图、甘特图等。<br><strong>在这里启用的选项对所有文章生效。你也可以选择不启用此处的设置，然后在需要启用的文章中对单篇文章启用。</strong><br>本主题的 Mermaid 使用示例为: <br><pre>```mermaid\ngraph TD\n    Start --&gt; Stop\n```</pre>Mermaid 语法可以参考: <a href=\"https://mermaidjs.github.io/flowchart.html\" target=\"_blank\">Mermaid 语法参考</a><br>Mermaid 在线编辑器: <a href=\"https://mermaidjs.github.io/mermaid-live-editor/\" target=\"_blank\">Mermaid Live Editor</a> 或使用 <a href=\"https://get233.com/archives/typora.html/\" target=\"_blank\">Typora</a>"));
    $form->addInput($mermaidBlock->multiMode());

    $form->addInput(new CollapseTitle('codeTitle', NULL, NULL, _mt('代码块'), NULL));
    $codeBlockOptions = new Typecho_Widget_Helper_Form_Element_Checkbox('codeBlockOptions',
        array(
            'codeDark' => _mt('始终显示暗色代码块'),
            'codeWrapLine' => _mt('在 Windows 上长代码自动换行'),
            'hideLineNumber' => _mt('不显示代码行号'),
        ),
        array(), _mt('代码块选项'), NULL);
    $form->addInput($codeBlockOptions->multiMode());

    $form->addInput(new CollapseTitle('pjaxTitle', NULL, NULL, _mt('PJAX'), NULL));
    $enablePjax = new Typecho_Widget_Helper_Form_Element_Select('enablePjax', array('0'=>_mt('不启用'), '1'=>_mt('启用 PJAX')), '0', _mt('启用 PJAX'),_mt('PJAX 可以让页面浏览时做到无刷新，但有些功能可能会存在兼容性问题。默认不启用'));
    $form->addInput($enablePjax);
    $pjaxLoadStyle = new Typecho_Widget_Helper_Form_Element_Select('pjaxLoadStyle', array('0'=>_mt('极简（默认）'), '1'=>_mt('点旋转（全屏）')), '0',
        _mt('PJAX 加载动画'), _mt('选择 PJAX 加载时的动画效果'));
    $form->addInput($pjaxLoadStyle->multiMode());
    $pjaxCompleteAction = new Typecho_Widget_Helper_Form_Element_Textarea('pjaxCompleteAction', NULL, NULL, _mt('PJAX RELOAD'), _mt('启用 PJAX 选项后, 你的第三方插件可能会在 PJAX 中失效。你可能需要在这里重新加载。<br>在这里写入你需要进行处理的 JS 代码。并确保正确,否则可能会导致后续 JS 代码无法执行。'));
    $form->addInput($pjaxCompleteAction);



    $form->addInput(new CollapseTitle('advancedTitle', NULL, NULL, _mt('高级选项'), NULL));
    $beian = new Typecho_Widget_Helper_Form_Element_Text('beian', NULL, NULL, _mt('备案许可号'), _mt('未备案则不需要填'));
    $form->addInput($beian);
    $postHeadContent = new Typecho_Widget_Helper_Form_Element_Textarea('postHeadContent', NULL, NULL, _mt('文章顶部内容'), _mt('这里添加的内容可以在所有文章的顶部展示，格式为 HTML，独立页面不会显示该内容'));
    $form->addInput($postHeadContent);
    $copyright = new Typecho_Widget_Helper_Form_Element_Textarea('copyright', NULL, NULL, _mt('版权声明'), _mt('在这里填入你自己的版权声明内容。<br>你可以使用'.Utils::toCode("{{title}}").'表示文章标题，用'.Utils::toCode('{{link}}').'表示文章链接。'));
    $form->addInput($copyright);
    $shortcutIcon = new Typecho_Widget_Helper_Form_Element_Hidden('shortcutIcon', NULL, NULL, _mt('Shortcut Icon'), _mt('留空则使用根目录下的「favicon.ico」文件'));
    $form->addInput($shortcutIcon);
    $realRealRealRealAdvancedOptions = new Typecho_Widget_Helper_Form_Element_Textarea('realRealRealRealAdvancedOptions', NULL, NULL, '<span style="color: #f2777a">'._mt('真 • 高级设置 <a href="https://get233.com/archives/mirages-hidden-settings.html" target="_blank">相关文档</a>').'</span>', _mt('设置实在太多啦，所以我把一些(我认为)不常用的设置取消了，改成在这里手动配置。'));
    $form->addInput($realRealRealRealAdvancedOptions);



    $form->addInput(new CollapseTitle('customExtendsTitle', NULL, NULL, _mt('主题自定义扩展'), _mt('该说明适用于该分类下的所有设置项<br>可以使用下列占位符输出特殊的信息:<br>'.Utils::toCode("{{%STATIC_PATH%}}").': 静态文件路径。如引入主题目录下的 css 文件, 可使用: '.Utils::toCode("&ltlink rel=\"stylesheet\" href=\"{{%STATIC_PATH%}}style.css\"&gt"))));
    $customHTMLInHeadTitle = new Typecho_Widget_Helper_Form_Element_Textarea('customHTMLInHeadTitle', NULL, NULL, _mt('自定义 HTML 元素拓展 - 标签: head 头部 (meta 元素后)'), _mt('在 head 标签头部(meta 元素后)添加你自己的 HTML 元素<br>你可以在这里拓展一些 meta 信息, 或一些其他信息。<br>某些统计代码可能要求被加入到尽可能靠前的位置, 那么你可以将其加入到这里。<br>不建议在这里添加 css'));
    $form->addInput($customHTMLInHeadTitle);
    $customHTMLInHeadBottom = new Typecho_Widget_Helper_Form_Element_Textarea('customHTMLInHeadBottom', NULL, NULL, _mt('自定义 HTML 元素拓展 - 标签: head 尾部 (head 标签结束前)'), _mt('在 head 尾部 (head 标签结束前)添加你自己的 HTML 元素<br>你可以在这里使用 link 标签引入你自己的 CSS 代码文件, 或直接使用 style 标签输出 css 代码, 或一些其他信息。<br>某些统计代码可能要求被加入到 head 标签中(如百度统计), 那么你可以将其加入到这里。'));
    $form->addInput($customHTMLInHeadBottom);
    $beforeBodyClose = new Typecho_Widget_Helper_Form_Element_Textarea('beforeBodyClose', NULL, NULL, _mt('自定义 HTML 元素拓展 - 在 body 标签结束前'), _mt('在 body 标签结束前添加你自己的 HTML 元素<br>你可以在这里使用 script 标签引入你自己的 js 代码文件, 或直接使用 script 标签输出 js 代码, 或一些其他信息。'));
    $form->addInput($beforeBodyClose);

    $form->addInput(new CollapseTitle('close', array("close"), NULL, NULL, NULL));

}

function themeFields(Typecho_Widget_Helper_Layout $layout) {
    echo Mirages::helloWrite();

    $fontFile = Mirages::$options->customFontFace__hasValue ? ('usr/' . Mirages::$options->customFontFace) : 'component/head_font.php';
    $themeName = Mirages::$options->theme;
    require Helper::options()->themeFile($themeName, $fontFile);

    $thumb = new Typecho_Widget_Helper_Form_Element_Textarea('banner', NULL, NULL, _mt('文章主图'), _mt('输入图片URL，如有多个则一行一个，随机选择展示。'));
    $layout->addItem($thumb);
//    $disableBanner = new Typecho_Widget_Helper_Form_Element_Select('disableBanner', array('0'=>_mt('对这篇文章显示文章主图'), '1'=>_mt('对这篇文章【不显示】文章主图')), '0', _mt('禁用文章主图'),_mt('在该页面不显示文章主图，即使配置了文章主图。'));
//    $layout->addItem($disableBanner);
//    $headTitle = new Typecho_Widget_Helper_Form_Element_Select('headTitle', array('0'=>_mt('跟随主题设置'), '1'=>_mt('对这篇文章开启'), '-1'=>_mt('对这篇文章禁用')), '0', _mt('标题显示在文章主图中'),_mt('标题显示在文章主图中，没有文章主图的目前不会显示。'));
//    $layout->addItem($headTitle);
    $disableDarkMask = new Typecho_Widget_Helper_Form_Element_Select('disableDarkMask', array('0'=>_mt('不禁用暗色透明遮罩'), '1'=>_mt('禁用暗色透明遮罩')), '0', _mt('禁用主图暗色透明遮罩'),_mt('标题显示在文章主图中的情况下，为了让标题可以清晰的显示，在文章主图中添加了暗色的透明遮罩，但如果主图本身就是暗色系，则没必要再次添加透明遮罩，再此选择禁用即可。'));
    $layout->addItem($disableDarkMask);
    $toc = new Typecho_Widget_Helper_Form_Element_Select('TOC', array('0'=>_mt('不启用'), '1'=>_mt('开启(默认隐藏)')), NULL, _mt('显示文章目录树'), NULL);
    $layout->addItem($toc);
    $contentLang = new Typecho_Widget_Helper_Form_Element_Select('contentLang', array('0'=>_mt('跟随主题设置'), 'zh'=>_mt('中文'), 'en'=>_mt('英语'), 'en_serif'=>_mt('英语(衬线体)')), NULL, _mt('文章语言'), _mt('针对特定语言的排版优化。如果文章中存在大量的英文段落或该文章仅包含英文，则请将此选项设置为英语，否则请保持默认。'));
    $layout->addItem($contentLang);
    $enableMathJax = new Typecho_Widget_Helper_Form_Element_Select('enableMathJax', array('0'=>_mt('跟随主题设置'), '1'=>_mt('为本篇文章显示数学公式 (MathJax)')), NULL, _mt('显示数学公式 (MathJax)'), _mt('在这里启用的流程图仅对本篇文章有效，且仅在使用卡片式文章列表的情况下有效。'));
    $layout->addItem($enableMathJax);
    $enableFlowChat = new Typecho_Widget_Helper_Form_Element_Select('enableFlowChat', array('0'=>_mt('跟随主题设置'), '1'=>_mt('为本篇文章启用流程图支持')), NULL, _mt('流程图支持'), _mt('在启用流程图支持后，您可以通过 Markdown 的流程图语法(非原生支持)来创建流程图。在这里启用的流程图仅对本篇文章有效，且仅在使用卡片式文章列表的情况下有效。'));
    $layout->addItem($enableFlowChat);
    $enableMermaid = new Typecho_Widget_Helper_Form_Element_Select('enableMermaid', array('0'=>_mt('跟随主题设置'), '1'=>_mt('为本篇文章启用 Mermaid')), NULL, _mt('Mermaid 支持'), _mt('在启用 Mermaid 支持后，您可以通过 Mermaid 的语法(非原生支持)来创建流程图、时序图、甘特图等。在这里启用的 Mermaid 仅对本篇文章有效，且仅在使用卡片式文章列表的情况下有效。'));
    $layout->addItem($enableMermaid);
    $layout->addItem(new Title('documentTitle', NULL, NULL, '', _mt('其他自定义字段使用提示(区分大小写)：').'<br>'.Utils::toCode('bannerHeight').', '.Utils::toCode('disableBanner').', '.Utils::toCode('headTitle').', '.Utils::toCode('mastheadTitle').', '.Utils::toCode('mastheadSubtitle').', '.Utils::toCode('mastheadTitleColor').', '.Utils::toCode('textAlign').', '.Utils::toCode('contentWidth').', '.Utils::toCode('hideCopyright').', '.Utils::toCode('css').', '.Utils::toCode('js').', '.Utils::toCode('redirect').', '.Utils::toCode('useSerifFont').'.<br><br>'._mt('相关用法请查看<a href="https://get233.com/archives/mirages-custom-fields.html" target="_blank">主题自定义字段使用帮助</a><br><br>使用时请点击左下角的「+添加字段」，然后在字段名称处键入上述可选值，类型为字符，在字段值处按字段要求键入相应的值即可。')));
}

function treeViewCategories($widget, $categoryOptions) {
    $classes = array();
    if ($categoryOptions->itemClass) {
        $classes[] = $categoryOptions->itemClass;
    }

    $classes[] = 'category-level-' . $widget->levels;

    echo '<' . $categoryOptions->itemTag . ' class="'
        . implode(' ', $classes);

    if ($widget->levels > 0) {
        echo ' category-child';
        $widget->levelsAlt(' category-level-odd', ' category-level-even');
    } else {
        echo ' category-parent';
    }

    if ($widget->mid == $widget->parameter->current) {
        echo ' category-active';
    } else if (isset($widget->_children[$widget->mid]) && in_array($widget->parameter->current, $widget->_children[$widget->mid])) {
        echo ' category-parent-active';
    }

    echo '"><a href="' . $widget->permalink . '">' . _mt($widget->name) . '</a>';

    if ($categoryOptions->showCount) {
        printf($categoryOptions->countTemplate, intval($widget->count));
    }

    if ($categoryOptions->showFeed) {
        printf($categoryOptions->feedTemplate, $widget->feedUrl);
    }

    if ($widget->children) {
        $widget->treeViewCategories();
    }

    echo '</li>';
    return NULL;
}