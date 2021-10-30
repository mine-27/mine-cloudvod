=== Connect ApsaraVideo VoD, OSS, Tencent Cloud VoD, COS to Wordpress with Aliplayer - by Mine CloudVod  ===
Contributors: mine27
Tags:阿里云视频点播,腾讯云点播,aliyun,alibaba cloud,tencentcloud,aliplayer,vod,qcloud,tcplayer,oss,cos,ApsaraVideo VoD
Donate link: http://pay.zwtt8.com/
Requires at least: 5.5
Tested up to: 5.8
Requires PHP: 7.0
Stable tag: 1.2.16
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The Best Cloud VoD Application. Aliplayer is the most reliable, easy to use and feature-rich video player. Supports Playlist, Multilingual Captions, Markers, Scroll Text, Countdown and so on.

== Description ==

Put your cloud video on wordpress safely and conveniently to play.

Upload videos directly to ApsaraVideo VoD, OSS, Tencent Cloud VoD, COS, which can automatic transcoding and encryption.

Supports embedding videos that support iframe calls such as Bilibili, Youku, Tencent Video, Iqiyi, etc. Click to view the <a href="https://www.zwtt8.com/docs-category/mine-cloudvod/embed/"> user document </a>.


## Mine Cloudvod is perfect for any wordpress website with video, such as: ##

* Blog
* Corporate Website
* Online Course Education
* Technical Training
* E-commerce
* Video Media
* All WordPress sites

## Features:##
* Built specifically for the Block Editor.
* <a href="https://www.zwtt8.com/docs/tutorlms/" target="_blank">Integrate the Tutor LMS plugin</a>.
* <a href="https://www.zwtt8.com/docs/b2/" target="_blank">Integrate B2 theme</a>, support video post list.
* <a href="https://www.zwtt8.com/docs/ripro-v2/" target="_blank">Integrate RiPro V2 theme</a>, support video post list.
* <a href="https://www.zwtt8.com/docs/elementor/" target="_blank">Integrate Elementor plugin</a>.
* Custom Video Cover Image.
* Custom Player Skin.
* AliPlayer Scroll Text.
* Multilingual Captions (<a href="https://www.zwtt8.com/plugins/2021-08-25/mine-cloudvod-aliyunvod-markers-captions/" target="_blank">example</a> ).
* **Video Markers** (<a href="https://www.zwtt8.com/plugins/2021-08-25/mine-cloudvod-aliyunvod-markers-captions/" target="_blank">example< /a>).
* Multiple AD Before Play
* Multiple AD On Pause
* Sticky Video Player
* Remember Played Position
* Cloud VoD Hub.
* Support ShortCodes.
* Support Function Calls, which can be placed anywhere in the theme code.
* Compatible with WeChat applet.
* Compatible with LearnDash LMS plugin.
* Compatible with LearnPress LMS plugin.

## Pro Features:##

* Support Alibaba Cloud VoD **HLS Standard Encryption** to make the video more secure.
* Upload video to **Alibaba Cloud VoD** directly, does not occupy server space and traffic.
* Upload video to **Alibaba Cloud OSS** directly, **Private Storage**, and play more securely.
* Upload video to **Tencent Cloud VoD** directly, does not occupy server space and traffic.
* Upload video to **Tencent Cloud COS** directly, **Private Storage**, and play more securely.
* <a href="https://www.zwtt8.com/docs/video-playlist/">Video Playlist</a>.

You can use Mine CloudVod for free, except for pro features. Pro features can be tried for free one month.

<a target="_blank" href="https://qm.qq.com/cgi-bin/qm/qr?k=uysDMHw6COzmy9taQNa0_v_yLYzevQFa&jump_from=webapi">MineCloudVod云点播（QQ群: 333858456）</a>
WeChat ID: MineCloudVod


== Installation ==
1. Upload the Mine CloudVod folder to the /wp-content/plugins/ directory.
2. Activate the Mine CloudVod plugin through the 'Plugins' menu in WordPress.

== Changelog ==

v1.2.16
    New Feature: Press space key to pause or start playback.
    New Feature: Click the video to pause or start playback.
    New Feature: Configure player logo.
    New Feature: Add categories of Alibaba Cloud VoD.

v1.2.15
    New Pro Feature: <a href="https://www.zwtt8.com/docs/video-playlist/">Video Playlist Block</a>.
    New Feature: Multiple scrolling text, random display.
    New Feature: Multiple Ads, random display.
    New Feature: Load subtitles from remote URL.
    Fix issue with the black frame in the image of Pause Ad.
    Fix issue with some knowned bugs.

v1.2.14
    New Feature: Customize the size of the sticky video.
    New Feature: Customize the text of the Live video ends.
    Fix issue with some links(https://v.qq.com/x/page/a3301jem0je.html) of Tencent Video could not be recognized.
    Fix issue with some known bugs.

v1.2.13
    Fix issue with the videos uploaded to Tencent Cloud VoD being transcoded multiple times.
    Fix issue with the size of the sticky video on the tablet.

v1.2.12
    Fix issue with video playback failed.
    New Feature: Video Ad Before Play.

v1.2.11
    Fix issue with Countdown page height.
    Fix issue with Some themes use apply_filters('the_content',$post_content) causing wp_add_inline_script to load multiple times.
    New Feature: Image AD Before Play.
    New Feature: Image AD On Pause.

v1.2.10
    修复：粘性视频手机端显示尺寸太小；
    修复：倒计时手机端样式兼容性；
    删除：媒体库下的submenu；
    归纳整理配置页面，更方便理解各配置选项；

v1.2.9
    修复：粘性视频被其他网页元素遮挡；
    修复：跑马灯逻辑，独立配置优先于全局配置；
    修复：Aliplayer记忆播放不支持英语；
    一些提示信息优化；

v1.2.8
    新功能：新增Aliplayer区块，支持添加mp4、m3u8链接，支持直播，并删除附加在阿里云视频点播区块的视频链接功能；
    新功能：Aliplayer增加倒计时播放视频或者直播；
    新功能：Aliplayer播放器随滚动条显示在屏幕指定位置；
    新功能：Aliplayer跑马灯支持为每个视频自定义滚动文本；

v1.2.7
    新功能：上传视频到腾讯云COS中，支持私有播放，告别被盗用烦恼;
    新功能：腾讯云超级播放器，增加记忆播放功能；
    新功能：<a href="https://www.zwtt8.com/docs/ripro-v2/">集成RiPro V2主题</a>视频模块，支持选集功能；

v1.2.6
    新功能：<a href="https://www.zwtt8.com/docs/elementor/" target="_blank">集成Elementor插件</a>；
    新功能：Aliplayer支持添加mp4、m3u8链接，支持直播；
    新功能：云点播中心点击简码、PHP函数复制到剪贴板；

v1.2.5
    修复：B2视频文章嵌入第三方视频不显示问题；

v1.2.4
    新功能：上传视频到阿里云OSS中，可私有播放

v1.2.3
    新功能：嵌入来自优酷、腾讯、爱奇艺、B站和其他支持iframe嵌入的视频
    新功能：支持Aliplayer跑马灯组件单独关闭功能
    优化一些英语翻译

v1.2.2
    新功能：AliPlayer记忆播放和倍速播放组件
    修复B2主题中播放腾讯云点播视频问题
    国际化

v1.2.1
    新功能：AliPlayer跑马灯功能，防录屏？

v1.2.0
    新增阿里云视频点播HLS标准加密播放功能；
    <a href="https://www.zwtt8.com/docs/b2/">集成B2主题</a>的视频文章，支持列表；

v1.1.10
    增加Aliplayer自定义字幕功能；
    增加Aliplayer视频打点功能；

v1.1.9
    增加云点播中心/枢纽；
    集成TutorLMS插件；

v1.1.8
    兼容微信小程序；

v1.1.7
    增加腾讯云点播gutenberg区块；

v1.1.6
    修复阿里云点播gutenberg区块上传和删除功能；

v1.1.5
    增加阿里云点播gutenberg区块；

v1.1.4
    修复根据用户id创建视频点播分类；

v1.1.3
    增加前台author用户直传阿里云视频点播功能；
    根据用户id创建视频点播分类；

v1.1.2
    修复根据视频ID添加阿里云视频点播到媒体库后不能播放问题；

v1.1.1
    新增阿里云视频点播的VideoId插入媒体库功能；
    新增高级功能 - 阿里云视频点播片头片尾设置；
    修复已知问题；

v1.1.0
    新增腾讯云点播的FileId插入媒体库功能；
    新增高级功能 - 腾讯云点播片头片尾设置；
    增加配置 - 媒体库上传是否上传到云点播

v1.0.7
    增加tcplayer配置

v1.0.6
    兼容同一文章中同时插入两个阿里云视频点播的视频播放窗口
    增加阿里云播放器aliplayer配置
    修复播放器样式加载

v1.0.5
    阿里云视频点播增加指定转码模板配置，视频上传后自动使用指定转码模板转码
    阿里云播放器增加清晰度选择，当转码有多个清晰度时显示
    修复已知问题

v1.0.4
    添加播放器样式设置功能
    修复阿里云播放器不显示问题

v1.0.3
    解决微信里不能播放问题

v1.0.21
    兼容5.7.2

v1.0.2
    支持Mine Video Player插件中调用通过本插件上传的腾讯云点播和阿里云视频点播中的视频

v1.0.1
    阿里云视频点播基础功能上线

v1.0.0
    腾讯云点播基础功能上线



== Screenshots ==
1. Video Playlist
2. ApsaraVideo VoD Block
3. Tencent Cloud VoD Block
4. Embed Video Block
5. Aliplayer Block
6. Video Playlist Block
7. The Settings of Aliplayer 
8. The Utility Components of Aliplayer 


== Frequently Asked Questions ==

= 支持嵌入YouTube视频吗 =
支持

= 支持嵌入Vimeo视频吗 =
支持