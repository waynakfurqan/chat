<?php
/**
 * Home page: hero, current courses, features, learning pathways, CTA.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logo_url  = (string) ASWJ_LMS_Settings::get( 'logo_url' );
$logged_in = is_user_logged_in();

$running = get_posts(
	array(
		'post_type'      => 'aswj_course',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'meta_key'       => '_aswj_running',
		'meta_value'     => '1',
	)
);
$all_count = (int) wp_count_posts( 'aswj_course' )->publish;
?>
<div class="aswj-lms aswj-home">

	<!-- Hero -->
	<section class="aswj-hero">
		<div class="aswj-hero-copy">
			<p class="aswj-hero-kicker"><?php esc_html_e( 'ASWJ Islamic College — Melbourne, Australia', 'aswj-lms' ); ?></p>
			<h1 class="aswj-hero-title"><?php echo esc_html( ASWJ_LMS_Settings::get( 'hero_title' ) ); ?></h1>
			<p class="aswj-hero-subtitle"><?php echo esc_html( ASWJ_LMS_Settings::get( 'hero_subtitle' ) ); ?></p>
			<div class="aswj-hero-actions">
				<a class="aswj-btn aswj-btn-lg" href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>"><?php esc_html_e( 'Browse Courses', 'aswj-lms' ); ?></a>
				<?php if ( $logged_in ) : ?>
					<a class="aswj-btn aswj-btn-lg aswj-btn-outline" href="<?php echo esc_url( ASWJ_LMS_Settings::portal_url() ); ?>"><?php esc_html_e( 'My Portal', 'aswj-lms' ); ?></a>
				<?php else : ?>
					<a class="aswj-btn aswj-btn-lg aswj-btn-outline" href="<?php echo esc_url( ASWJ_LMS_Settings::registration_url() ); ?>"><?php esc_html_e( 'Create Free Account', 'aswj-lms' ); ?></a>
				<?php endif; ?>
			</div>
			<p class="aswj-hero-arabic" lang="ar" dir="rtl">معهد أهل السنة والجماعة</p>
		</div>
		<div class="aswj-hero-art" aria-hidden="true">
			<?php if ( $logo_url ) : ?>
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="" loading="lazy" />
			<?php else : ?>
				<svg viewBox="0 0 220 260" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
					<path d="M10 30 L95 8 L95 90 L52 102 L52 230 L10 205 Z" fill="var(--aswj-blue)"/>
					<path d="M60 52 L145 30 L145 112 L102 124 L102 252 L60 227 Z" fill="var(--aswj-green)" opacity="0.95"/>
				</svg>
			<?php endif; ?>
		</div>
	</section>

	<!-- Current courses -->
	<?php if ( $running ) : ?>
	<section class="aswj-home-section">
		<div class="aswj-section-head">
			<h2><?php esc_html_e( 'Current Courses', 'aswj-lms' ); ?></h2>
			<p class="aswj-muted"><?php esc_html_e( 'Now running — registrations open. Join us, in shaa Allah.', 'aswj-lms' ); ?></p>
		</div>
		<div class="aswj-course-grid">
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
	</section>
	<?php endif; ?>

	<!-- Features -->
	<section class="aswj-home-section aswj-home-section-soft">
		<div class="aswj-section-head">
			<h2><?php esc_html_e( 'Why study with ASWJ College?', 'aswj-lms' ); ?></h2>
		</div>
		<div class="aswj-feature-grid">
			<div class="aswj-feature-card">
				<span class="aswj-feature-icon" aria-hidden="true">&#128214;</span>
				<h3><?php esc_html_e( 'Authentic Knowledge', 'aswj-lms' ); ?></h3>
				<p><?php esc_html_e( 'Every course is grounded in the Quran and the authentic Sunnah, upon the understanding of the righteous predecessors.', 'aswj-lms' ); ?></p>
			</div>
			<div class="aswj-feature-card">
				<span class="aswj-feature-icon" aria-hidden="true">&#127891;</span>
				<h3><?php esc_html_e( 'Structured Learning', 'aswj-lms' ); ?></h3>
				<p><?php esc_html_e( 'Courses are broken into ordered video lessons. Tick lessons off as you go and watch your progress grow.', 'aswj-lms' ); ?></p>
			</div>
			<div class="aswj-feature-card">
				<span class="aswj-feature-icon" aria-hidden="true">&#128241;</span>
				<h3><?php esc_html_e( 'Learn Anywhere', 'aswj-lms' ); ?></h3>
				<p><?php esc_html_e( 'Study from your phone, tablet or computer — at home, on the train, whenever suits your schedule.', 'aswj-lms' ); ?></p>
			</div>
		</div>
	</section>

	<!-- Pathways -->
	<section class="aswj-home-section">
		<div class="aswj-section-head">
			<h2><?php esc_html_e( 'Ways to Study', 'aswj-lms' ); ?></h2>
			<p class="aswj-muted">
				<?php
				printf(
					/* translators: %d: number of published courses */
					esc_html( _n( '%d course available', '%d courses available', $all_count, 'aswj-lms' ) ),
					(int) $all_count
				);
				?>
			</p>
		</div>
		<div class="aswj-pathway-grid">
			<div class="aswj-pathway-card">
				<h3><?php esc_html_e( 'Free Courses', 'aswj-lms' ); ?></h3>
				<p><?php esc_html_e( 'Open to everyone. Create a free account to track your progress.', 'aswj-lms' ); ?></p>
				<a class="aswj-btn aswj-btn-outline aswj-btn-block" href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>"><?php esc_html_e( 'Start Learning', 'aswj-lms' ); ?></a>
			</div>
			<div class="aswj-pathway-card">
				<h3><?php esc_html_e( 'Single Courses', 'aswj-lms' ); ?></h3>
				<p><?php esc_html_e( 'Enroll in an individual course with a one-time payment — pay online, or by bank transfer / cash in person.', 'aswj-lms' ); ?></p>
				<a class="aswj-btn aswj-btn-outline aswj-btn-block" href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>"><?php esc_html_e( 'Browse Courses', 'aswj-lms' ); ?></a>
			</div>
			<div class="aswj-pathway-card aswj-pathway-featured">
				<span class="aswj-badge aswj-badge-free"><?php esc_html_e( 'Best Value', 'aswj-lms' ); ?></span>
				<h3><?php esc_html_e( 'Monthly All-Access', 'aswj-lms' ); ?></h3>
				<p><?php esc_html_e( 'One monthly subscription unlocks every paid course — including new courses as they are released. Cancel anytime.', 'aswj-lms' ); ?></p>
				<?php if ( ASWJ_LMS_Settings::subscribe_url() ) : ?>
					<a class="aswj-btn aswj-btn-block" href="<?php echo esc_url( ASWJ_LMS_Settings::subscribe_url() ); ?>"><?php esc_html_e( 'Subscribe', 'aswj-lms' ); ?></a>
				<?php endif; ?>
			</div>
			<div class="aswj-pathway-card">
				<h3><?php esc_html_e( 'Diploma Program', 'aswj-lms' ); ?></h3>
				<p><?php esc_html_e( 'Our structured multi-year program for dedicated students, with exclusive course content.', 'aswj-lms' ); ?></p>
				<a class="aswj-btn aswj-btn-outline aswj-btn-block" href="mailto:<?php echo esc_attr( ASWJ_LMS_Settings::get( 'contact_email' ) ); ?>"><?php esc_html_e( 'Enquire', 'aswj-lms' ); ?></a>
			</div>
		</div>
	</section>

	<!-- CTA -->
	<section class="aswj-cta-banner">
		<h2><?php esc_html_e( 'Begin your journey of knowledge today', 'aswj-lms' ); ?></h2>
		<p><?php esc_html_e( '“Whoever follows a path in pursuit of knowledge, Allah will make easy for him a path to Paradise.” — Sahih Muslim', 'aswj-lms' ); ?></p>
		<div class="aswj-hero-actions">
			<?php if ( $logged_in ) : ?>
				<a class="aswj-btn aswj-btn-lg aswj-btn-hero" href="<?php echo esc_url( ASWJ_LMS_Settings::portal_url() ); ?>"><?php esc_html_e( 'Go to My Portal', 'aswj-lms' ); ?></a>
			<?php else : ?>
				<a class="aswj-btn aswj-btn-lg aswj-btn-hero" href="<?php echo esc_url( ASWJ_LMS_Settings::registration_url() ); ?>"><?php esc_html_e( 'Create Free Account', 'aswj-lms' ); ?></a>
			<?php endif; ?>
		</div>
		<div class="aswj-social-links">
			<?php if ( ASWJ_LMS_Settings::get( 'instagram_url' ) ) : ?>
				<a href="<?php echo esc_url( ASWJ_LMS_Settings::get( 'instagram_url' ) ); ?>" target="_blank" rel="noopener">Instagram @aswjcollege</a>
			<?php endif; ?>
			<?php if ( ASWJ_LMS_Settings::get( 'facebook_url' ) ) : ?>
				<a href="<?php echo esc_url( ASWJ_LMS_Settings::get( 'facebook_url' ) ); ?>" target="_blank" rel="noopener">Facebook @aswjcollegemelb</a>
			<?php endif; ?>
		</div>
	</section>
</div>
