<?php
/**
 * Home page, per the aswj-design-kit brief:
 * hero (arch + hadith) · stat facts · current courses · how it works ·
 * membership · FAQ · closing CTA with Qur'anic du'a.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logged_in = is_user_logged_in();

/* Hero headline: text after | renders as the italic serif clause. */
$hero_title_raw = (string) ASWJ_LMS_Settings::get( 'hero_title' );
$hero_parts     = array_map( 'trim', explode( '|', $hero_title_raw, 2 ) );

$running = get_posts(
	array(
		'post_type'      => 'aswj_course',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'meta_key'       => '_aswj_running',
		'meta_value'     => '1',
	)
);

$course_count = (int) wp_count_posts( 'aswj_course' )->publish;
$lesson_count = (int) wp_count_posts( 'aswj_lesson' )->publish;
$free_count   = count(
	get_posts(
		array(
			'post_type'      => 'aswj_course',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_aswj_access_type',
			'meta_value'     => 'free',
		)
	)
);
?>
<div class="aswj-lms aw aw-home">

	<!-- Hero -->
	<section class="aw-hero">
		<div>
			<p class="aw-eyebrow"><?php esc_html_e( 'ASWJ Islamic College — Melbourne, Australia', 'aswj-lms' ); ?></p>
			<h1 class="aw-hero__title">
				<?php echo esc_html( $hero_parts[0] ); ?>
				<?php if ( ! empty( $hero_parts[1] ) ) : ?>
					<em><?php echo esc_html( $hero_parts[1] ); ?></em>
				<?php endif; ?>
			</h1>
			<p class="aw-lede aw-hero__lede"><?php echo esc_html( ASWJ_LMS_Settings::get( 'hero_subtitle' ) ); ?></p>
			<div class="aw-hero__actions">
				<a class="aw-btn aw-btn--primary aw-btn--lg" href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>"><?php esc_html_e( 'Browse courses', 'aswj-lms' ); ?></a>
				<?php if ( $logged_in ) : ?>
					<a class="aw-btn aw-btn--secondary aw-btn--lg" href="<?php echo esc_url( ASWJ_LMS_Settings::portal_url() ); ?>"><?php esc_html_e( 'My portal', 'aswj-lms' ); ?></a>
				<?php else : ?>
					<a class="aw-btn aw-btn--secondary aw-btn--lg" href="<?php echo esc_url( ASWJ_LMS_Settings::registration_url() ); ?>"><?php esc_html_e( 'Create a free account', 'aswj-lms' ); ?></a>
				<?php endif; ?>
			</div>
			<ul class="aw-hero__ticks">
				<li><?php esc_html_e( 'Authentic sources', 'aswj-lms' ); ?></li>
				<li><?php esc_html_e( 'Self-paced video lessons', 'aswj-lms' ); ?></li>
				<li><?php esc_html_e( 'Progress you can see', 'aswj-lms' ); ?></li>
			</ul>
		</div>
		<div class="aw-hero__arch aw-arch aw-pattern">
			<?php echo ASWJ_LMS_Templates::mark_svg(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<p class="aw-hero__hadith" lang="ar" dir="rtl">وَمَنْ سَلَكَ طَرِيقًا يَلْتَمِسُ فِيهِ عِلْمًا سَهَّلَ اللَّهُ لَهُ بِهِ طَرِيقًا إِلَى الْجَنَّةِ</p>
			<p class="aw-hero__translation"><?php esc_html_e( 'Whoever follows a path in pursuit of knowledge, Allah makes easy for him a path to Paradise.', 'aswj-lms' ); ?></p>
			<p class="aw-hero__source"><?php esc_html_e( 'Sahih Muslim', 'aswj-lms' ); ?></p>
		</div>
	</section>

	<!-- Facts -->
	<section class="aw-section">
		<div class="aw-stats">
			<div class="aw-stat"><b><?php echo (int) $course_count; ?></b><span><?php echo esc_html( _n( 'course', 'courses', $course_count, 'aswj-lms' ) ); ?></span></div>
			<div class="aw-stat"><b><?php echo (int) $lesson_count; ?></b><span><?php echo esc_html( _n( 'video lesson', 'video lessons', $lesson_count, 'aswj-lms' ) ); ?></span></div>
			<div class="aw-stat"><b><?php echo (int) $free_count; ?></b><span><?php echo esc_html( _n( 'free course', 'free courses', $free_count, 'aswj-lms' ) ); ?></span></div>
			<div class="aw-stat"><b><?php esc_html_e( 'Online', 'aswj-lms' ); ?></b><span><?php esc_html_e( 'self-paced, on any device', 'aswj-lms' ); ?></span></div>
		</div>
	</section>

	<!-- Current courses -->
	<?php if ( $running ) : ?>
	<section class="aw-section">
		<div class="aw-band">
			<div class="aw-section-head">
				<p class="aw-eyebrow"><?php esc_html_e( 'Now running', 'aswj-lms' ); ?></p>
				<h2 class="aw-heading-1"><?php esc_html_e( 'Current courses', 'aswj-lms' ); ?> <em><?php esc_html_e( 'enrolment open', 'aswj-lms' ); ?></em></h2>
			</div>
			<div class="aw-course-grid">
				<?php
				foreach ( $running as $running_course ) {
					ASWJ_LMS_Templates::part(
						'course-card',
						array(
							'course'  => $running_course,
							'user_id' => get_current_user_id(),
						)
					);
				}
				?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<!-- How it works -->
	<section class="aw-section">
		<div class="aw-section-head">
			<p class="aw-eyebrow"><?php esc_html_e( 'How it works', 'aswj-lms' ); ?></p>
			<h2 class="aw-heading-1"><?php esc_html_e( 'Begin in three steps', 'aswj-lms' ); ?></h2>
		</div>
		<div class="aw-pillars">
			<div class="aw-pillar">
				<i>01</i>
				<h3><?php esc_html_e( 'Create your account', 'aswj-lms' ); ?></h3>
				<p><?php esc_html_e( 'One free student account gives you your own portal, and your details are remembered for every future course registration.', 'aswj-lms' ); ?></p>
			</div>
			<div class="aw-pillar">
				<i>02</i>
				<h3><?php esc_html_e( 'Choose your course', 'aswj-lms' ); ?></h3>
				<p><?php esc_html_e( 'Begin with a free course, enrol in a single course, or subscribe monthly for access to everything.', 'aswj-lms' ); ?></p>
			</div>
			<div class="aw-pillar">
				<i>03</i>
				<h3><?php esc_html_e( 'Learn and track', 'aswj-lms' ); ?></h3>
				<p><?php esc_html_e( 'Watch each lesson, mark it complete, and watch your progress grow from the first lesson to the last.', 'aswj-lms' ); ?></p>
			</div>
		</div>
	</section>

	<!-- Membership -->
	<section class="aw-section">
		<div class="aw-section-head">
			<p class="aw-eyebrow"><?php esc_html_e( 'Membership', 'aswj-lms' ); ?></p>
			<h2 class="aw-heading-1"><?php esc_html_e( 'Ways to study', 'aswj-lms' ); ?> <em><?php esc_html_e( 'with the college', 'aswj-lms' ); ?></em></h2>
		</div>
		<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:24px;align-items:start">
			<div class="aw-plan">
				<h3 class="aw-heading-3"><?php esc_html_e( 'Free courses', 'aswj-lms' ); ?></h3>
				<p class="aw-muted aw-small"><?php esc_html_e( 'Open to everyone. Create a free account to keep your progress.', 'aswj-lms' ); ?></p>
				<a class="aw-btn aw-btn--secondary aw-btn--block" href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>"><?php esc_html_e( 'Start learning', 'aswj-lms' ); ?></a>
			</div>
			<div class="aw-plan">
				<h3 class="aw-heading-3"><?php esc_html_e( 'Single course', 'aswj-lms' ); ?></h3>
				<p class="aw-muted aw-small"><?php esc_html_e( 'A one-time payment for lifetime access — pay online, or by bank transfer or cash in person.', 'aswj-lms' ); ?></p>
				<a class="aw-btn aw-btn--secondary aw-btn--block" href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>"><?php esc_html_e( 'Browse courses', 'aswj-lms' ); ?></a>
			</div>
			<div class="aw-plan aw-plan--featured aw-pattern">
				<span class="aw-badge aw-badge--glass"><?php esc_html_e( 'All-access', 'aswj-lms' ); ?></span>
				<h3 class="aw-heading-3"><?php esc_html_e( 'Monthly membership', 'aswj-lms' ); ?></h3>
				<p class="aw-small"><?php esc_html_e( 'One subscription unlocks every paid course, including new courses as they are released. Cancel anytime.', 'aswj-lms' ); ?></p>
				<?php if ( ASWJ_LMS_Settings::subscribe_url() ) : ?>
					<a class="aw-btn aw-btn--light aw-btn--block" href="<?php echo esc_url( ASWJ_LMS_Settings::subscribe_url() ); ?>"><?php esc_html_e( 'View membership', 'aswj-lms' ); ?></a>
				<?php endif; ?>
			</div>
			<div class="aw-plan">
				<h3 class="aw-heading-3"><?php esc_html_e( 'Diploma program', 'aswj-lms' ); ?></h3>
				<p class="aw-muted aw-small"><?php esc_html_e( 'Our structured program for dedicated students, with exclusive course content.', 'aswj-lms' ); ?></p>
				<a class="aw-btn aw-btn--secondary aw-btn--block" href="mailto:<?php echo esc_attr( ASWJ_LMS_Settings::get( 'contact_email' ) ); ?>"><?php esc_html_e( 'Enquire', 'aswj-lms' ); ?></a>
			</div>
		</div>
	</section>

	<!-- FAQ -->
	<section class="aw-section">
		<div class="aw-section-head">
			<p class="aw-eyebrow"><?php esc_html_e( 'Questions', 'aswj-lms' ); ?></p>
			<h2 class="aw-heading-1"><?php esc_html_e( 'Before you begin', 'aswj-lms' ); ?></h2>
		</div>
		<div class="aw-acc" data-aswj-accordion>
			<div class="aw-acc__item">
				<button type="button" class="aw-acc__q" aria-expanded="false"><?php esc_html_e( 'Do I need an account to watch the free courses?', 'aswj-lms' ); ?></button>
				<div class="aw-acc__a"><?php esc_html_e( 'No — free courses are open to everyone. An account lets you tick lessons off and keep your progress, and it is free to create.', 'aswj-lms' ); ?></div>
			</div>
			<div class="aw-acc__item">
				<button type="button" class="aw-acc__q" aria-expanded="false"><?php esc_html_e( 'How do I pay for a course?', 'aswj-lms' ); ?></button>
				<div class="aw-acc__a"><?php esc_html_e( 'You can pay online for instant access, or choose bank transfer or cash in person — the college unlocks your course once the payment is verified.', 'aswj-lms' ); ?></div>
			</div>
			<div class="aw-acc__item">
				<button type="button" class="aw-acc__q" aria-expanded="false"><?php esc_html_e( 'What does the monthly membership include?', 'aswj-lms' ); ?></button>
				<div class="aw-acc__a"><?php esc_html_e( 'Every paid course on the site, including courses released after you subscribe. Courses you purchase individually remain yours even if you cancel.', 'aswj-lms' ); ?></div>
			</div>
			<div class="aw-acc__item">
				<button type="button" class="aw-acc__q" aria-expanded="false"><?php esc_html_e( 'Are there classes only for sisters?', 'aswj-lms' ); ?></button>
				<div class="aw-acc__a"><?php esc_html_e( 'Yes. Some courses are exclusively for our sisters — they unlock automatically for accounts registered as sisters.', 'aswj-lms' ); ?></div>
			</div>
		</div>
	</section>

	<!-- Closing CTA -->
	<section class="aw-deep aw-pattern aw-cta">
		<p class="aw-arabic-line" lang="ar" dir="rtl">رَبِّ زِدْنِي عِلْمًا</p>
		<h2><?php esc_html_e( 'My Lord, increase me in knowledge.', 'aswj-lms' ); ?></h2>
		<p><?php esc_html_e( 'Surah Ta-Ha, 20:114. Begin your journey of seeking knowledge with us.', 'aswj-lms' ); ?></p>
		<div class="aw-cta__actions">
			<?php if ( $logged_in ) : ?>
				<a class="aw-btn aw-btn--light aw-btn--lg" href="<?php echo esc_url( ASWJ_LMS_Settings::portal_url() ); ?>"><?php esc_html_e( 'Go to my portal', 'aswj-lms' ); ?></a>
			<?php else : ?>
				<a class="aw-btn aw-btn--light aw-btn--lg" href="<?php echo esc_url( ASWJ_LMS_Settings::registration_url() ); ?>"><?php esc_html_e( 'Create a free account', 'aswj-lms' ); ?></a>
			<?php endif; ?>
			<a class="aw-btn aw-btn--outline-light aw-btn--lg" href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>"><?php esc_html_e( 'Browse courses', 'aswj-lms' ); ?></a>
		</div>
		<div class="aw-cta__social">
			<?php if ( ASWJ_LMS_Settings::get( 'instagram_url' ) ) : ?>
				<a href="<?php echo esc_url( ASWJ_LMS_Settings::get( 'instagram_url' ) ); ?>" target="_blank" rel="noopener">Instagram — @aswjcollege</a>
			<?php endif; ?>
			<?php if ( ASWJ_LMS_Settings::get( 'facebook_url' ) ) : ?>
				<a href="<?php echo esc_url( ASWJ_LMS_Settings::get( 'facebook_url' ) ); ?>" target="_blank" rel="noopener">Facebook — @aswjcollegemelb</a>
			<?php endif; ?>
		</div>
	</section>
</div>
