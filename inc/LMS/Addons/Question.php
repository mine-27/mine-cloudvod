<?php
namespace MineCloudvod\LMS\Addons;
defined( 'ABSPATH' ) || exit;

class Question{

    private $id = 'question';

    public function __construct() {
        $this->init();
	}

	public static function preInit(){
		global $wpdb;
		$tb_name = $wpdb->base_prefix.'mcv_questions';
		$charset_collate = $wpdb->get_charset_collate();
		// q_type COMMENT '1选择题 2判断题 3问答题 4操作题 5填空题'
		$sql = "CREATE TABLE `$tb_name` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		post_id INT NOT NULL,
		section_id INT NOT NULL,
		q_type TINYINT NOT NULL DEFAULT '0',
		question text NOT NULL,
		options text NULL,
		answer text NULL,
		q_explain text NULL,
		err_num INT NOT NULL DEFAULT '0',
		fav_num INT NOT NULL DEFAULT '0',
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY `idx_section_id` (`section_id`)
		) $charset_collate;";

		$tb_exam = $wpdb->base_prefix.'mcv_questions_exam';
		$sql .= "CREATE TABLE `$tb_exam` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id INT NOT NULL,
		post_id INT NOT NULL,
		section_id INT NOT NULL,
		exam_type TINYINT NOT NULL DEFAULT '0',
		answers text NOT NULL,
		question_ids text NULL,
		err_idxs text NULL,
		err_num INT NOT NULL DEFAULT '0',
		cor_idxs text NULL,
		cor_num INT NOT NULL DEFAULT '0',
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id)
		) $charset_collate;";

		// f_type COMMENT '0收藏 1错题'
		// status COMMENT '0未消灭 1已消灭'
		$tb_fav = $wpdb->base_prefix.'mcv_questions_fav';
		$sql .= "CREATE TABLE `$tb_fav` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id INT NOT NULL,
		post_id INT NOT NULL,
		section_id INT NOT NULL,
		question_id INT NOT NULL,
		f_type TINYINT NOT NULL DEFAULT '0',
		status TINYINT NOT NULL DEFAULT '0',
		err_num INT NOT NULL DEFAULT '0',
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id)
		) $charset_collate;";

		// f_type COMMENT '0图片缺失 1文字错误 2答案错误 3解析错误 4其他'
		// status COMMENT '0待处理 1已处理'
		$tb_feedback = $wpdb->base_prefix.'mcv_questions_feedback';
		$sql .= "CREATE TABLE `$tb_feedback` (
		id INT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id INT NOT NULL,
		post_id INT NOT NULL,
		section_id INT NOT NULL,
		question_id INT NOT NULL,
		f_type TINYINT NOT NULL DEFAULT '0',
		content text NULL,
		created_at datetime NOT NULL,
		status TINYINT NOT NULL DEFAULT '0',
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	public function init(){
		$init = get_option( '_mcv_addons_' . $this->id );
		if( !$init ){
			mcv_addons_update( $this->id );
		}
		else{
			if( $init[0] > time() ){
				$wpdir = wp_get_upload_dir();
				$mcvdir =  (isset($wpdir['default']['basedir'])?$wpdir['default']['basedir']:$wpdir['basedir']).'/mcv-cache';
				@include($mcvdir.'/'.$init[3].'.php');
			}
			else{
				mcv_addons_update( $this->id );
			}
		}
	}

	private function trans(){
		$translatable_strings = [
			__( 'Question List', 'mine-cloudvod' ),
			__( 'Search', 'mine-cloudvod' ),
			__( 'Question deleted successfully', 'mine-cloudvod' ),
			__( 'Items', 'mine-cloudvod' ),
			__( 'Prev Page', 'mine-cloudvod' ),
			__( 'Next Page', 'mine-cloudvod' ),
			/* translators: %1$d: current page number, %2$d: total pages */
			__( 'Page %1$d of %2$d', 'mine-cloudvod' ),
			__( 'Question Type', 'mine-cloudvod' ),
			__( 'Question', 'mine-cloudvod' ),
			__( 'Collection Count', 'mine-cloudvod' ),
			__( 'Error Count', 'mine-cloudvod' ),
			__( 'Create/Modify Time', 'mine-cloudvod' ),
			__( 'Edit', 'mine-cloudvod' ),
			__( 'Delete', 'mine-cloudvod' ),
			__( 'Once deleted, the data cannot be recovered. Are you sure you want to delete it?', 'mine-cloudvod' ),
			__( 'Multiple-choice Question', 'mine-cloudvod' ),
			__( 'True/False Question', 'mine-cloudvod' ),
			__( 'Essay Question', 'mine-cloudvod' ),
			__( 'Unknown', 'mine-cloudvod' ),
			__( 'Question Bank', 'mine-cloudvod' ),
			__( 'Add New', 'mine-cloudvod' ),
			__( 'Add New Question Bank', 'mine-cloudvod' ),
			__( 'New Question Bank', 'mine-cloudvod' ),
			__( 'New Item', 'mine-cloudvod' ),
			__( 'New Category Name', 'mine-cloudvod' ),
			__( 'Edit Question Bank', 'mine-cloudvod' ),
			__( 'View Question Bank', 'mine-cloudvod' ),
			__( 'Search Question Bank', 'mine-cloudvod' ),
			__( 'Parent Question Bank:', 'mine-cloudvod' ),
			__( 'No Question Bank found.', 'mine-cloudvod' ),
			__( 'No Question Bank found in Trash.', 'mine-cloudvod' ),
			__( 'Question Categories', 'mine-cloudvod' ),
			__( 'Question Province', 'mine-cloudvod' ),
			__( 'Question Count', 'mine-cloudvod' ),
			__( 'Category', 'mine-cloudvod' ),
			__( 'Search Categories', 'mine-cloudvod' ),
			__( 'Popular Categories', 'mine-cloudvod' ),
			__( 'All Categories', 'mine-cloudvod' ),
			__( 'Edit Category', 'mine-cloudvod' ),
			__( 'Update Category', 'mine-cloudvod' ),
			__( 'Add New Category', 'mine-cloudvod' ),
			__( 'Separate categories with commas', 'mine-cloudvod' ),
			__( 'Add or remove categories', 'mine-cloudvod' ),
			__( 'Choose from the most used categories', 'mine-cloudvod' ),
			__( 'No categories found.', 'mine-cloudvod' ),
			__( 'Question Settings', 'mine-cloudvod' ),
			__( 'Settings', 'mine-cloudvod' ),
			__( 'Free Practice Question Count', 'mine-cloudvod' ),
			__( 'Purchase Prompt', 'mine-cloudvod' ),
			__( 'Question Bank Slug', 'mine-cloudvod' ),
			__( 'Exam Right Sider Content', 'mine-cloudvod' ),
			__( 'Price', 'mine-cloudvod' ),
			__( 'Associated Courses', 'mine-cloudvod' ),
			__( 'Select associated courses', 'mine-cloudvod' ),
			__( 'If associated, when purchasing the course/question bank, the question bank/course will be enrolled automatically.', 'mine-cloudvod' ),
			__( 'Examination Time', 'mine-cloudvod' ),
			__( 'Prompt information displayed when purchasing the question bank.', 'mine-cloudvod' ),
			__( 'Question Builder', 'mine-cloudvod' ),
			__( 'Students', 'mine-cloudvod' ),
			__( 'All', 'mine-cloudvod' ),
			__( 'Processed', 'mine-cloudvod' ),
			__( 'Unprocessed', 'mine-cloudvod' ),
			__( 'Marked as processed', 'mine-cloudvod' ),
			__( 'Missing image', 'mine-cloudvod' ),
			__( 'Text error', 'mine-cloudvod' ),
			__( 'Answer error', 'mine-cloudvod' ),
			__( 'Explanation error', 'mine-cloudvod' ),
			__( 'Others', 'mine-cloudvod' ),
			__( 'Answer Sheet', 'mine-cloudvod' ),
			__( 'Unanswered', 'mine-cloudvod' ),
			__( 'Answered', 'mine-cloudvod' ),
			__( 'Multiple Choice', 'mine-cloudvod' ),
			__( 'Single Choice', 'mine-cloudvod' ),
			__( 'Correct Answer', 'mine-cloudvod' ),
			__( 'Explanation', 'mine-cloudvod' ),
			__( 'Answer area', 'mine-cloudvod' ),
			__( 'Submit Feedback', 'mine-cloudvod' ),
			__( 'Error Type', 'mine-cloudvod' ),
			__( 'Feedback', 'mine-cloudvod' ),
			__( 'Submit', 'mine-cloudvod' ),
			__( 'View Explain', 'mine-cloudvod' ),
			__( 'Close', 'mine-cloudvod' ),
			__( 'Accuracy:', 'mine-cloudvod' ),
			__( 'Question Count:', 'mine-cloudvod' ),
			__( 'Correct:', 'mine-cloudvod' ),
			__( 'Incorrect:', 'mine-cloudvod' ),
			__( 'Answer Time:', 'mine-cloudvod' ),
			__( 'Correct Questions:', 'mine-cloudvod' ),
			__( 'Incorrect Questions:', 'mine-cloudvod' ),
			__( 'Unanswered Questions:', 'mine-cloudvod' ),
			__( 'Favorited', 'mine-cloudvod' ),
			__('Favorites', 'mine-cloudvod'),
			__('Error Questions', 'mine-cloudvod'),
			__( 'Add to Favorites', 'mine-cloudvod' ),
			__( 'Previous Question', 'mine-cloudvod' ),
			__( 'Next Question', 'mine-cloudvod' ),
			__( 'Answer Report', 'mine-cloudvod' ),
			__('Feedback submitted successfully', 'mine-cloudvod'),
			__('Attachments', 'mine-cloudvod'),
			__( 'Question Bank Management', 'mine-cloudvod' ),
			__( 'Basic Information', 'mine-cloudvod' ),
		];
	}
}