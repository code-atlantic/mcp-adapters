<?php
/**
 * Create Complete Email Sequence with Smart Link
 *
 * Run: wp eval-file create-sequence-with-emails.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	require_once __DIR__ . '/../../../wp-load.php';
}

// Check FluentCRM is active
if ( ! defined( 'FLUENTCRM' ) ) {
	echo "❌ FluentCRM not active\n";
	exit( 1 );
}

echo "\n🎯 Creating Complete Email Sequence with Smart Link\n";
echo str_repeat( '=', 70 ) . "\n";

try {
	// Step 1: Create Smart Link
	echo "\n📍 Step 1: Creating Smart Link for Tracking...\n";

	$smart_link = \FluentCampaign\App\Models\CampaignUrlMetric::create([
		'type'       => 'smart_url',
		'url'        => 'https://example.com/welcome-hub',
		'slug'       => 'welcome-resources-' . time(),
		'short'      => \FluentCrm\Framework\Support\Str::random( 8 ),
		'title'      => 'Welcome Resources Hub',
		'created_by' => get_current_user_id(),
		'status'     => 'published',
	]);

	echo "✅ Smart Link Created!\n";
	echo "   ID: {$smart_link->id}\n";
	echo '   Short URL: ' . site_url() . "/r/{$smart_link->short}\n";

	$short_url = site_url() . "/r/{$smart_link->short}";

	// Step 2: Create Sequence
	echo "\n📧 Step 2: Creating Email Sequence...\n";

	$sequence = \FluentCampaign\App\Models\Sequence::create([
		'title'       => 'Welcome Email Series - 5 Days',
		'slug'        => 'welcome-series-' . time(),
		'description' => 'Comprehensive welcome sequence to onboard new subscribers',
		'status'      => 'published',
		'settings'    => [
			'email_subject_prefix'    => '',
			'allow_re_enrollment'     => false,
			'unsubscribe_on_complete' => false,
		],
		'created_by'  => get_current_user_id(),
	]);

	echo "✅ Sequence Created!\n";
	echo "   ID: {$sequence->id}\n";
	echo "   Status: {$sequence->status}\n";

	// Step 3: Add 5 Emails to Sequence
	echo "\n✉️  Step 3: Adding 5 Emails to Sequence...\n\n";

	$emails = [
		[
			'subject' => 'Welcome! Here\'s What to Expect 🎉',
			'delay'   => 0,
			'body'    => <<<HTML
<!-- wp:image {"align":"center","width":"200px"} -->
<figure class="wp-block-image aligncenter"><img src="https://via.placeholder.com/200x60/4A9B7F/FFFFFF?text=LOGO" alt="Logo" style="width:200px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Hi {{contact.first_name}},</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="has-text-align-center">Welcome to Our Community!</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">We're thrilled to have you here. Over the next 5 days, we'll share valuable resources to help you get started.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"width":50} -->
<div class="wp-block-button has-custom-width wp-block-button__width-50"><a class="wp-block-button__link" href="{$short_url}">Get Started Now</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
HTML
			,
		],
		[
			'subject' => 'Day 2: Your First Steps to Success 🚀',
			'delay'   => 24, // 1 day
			'body'    => <<<HTML
<!-- wp:image {"align":"center","width":"200px"} -->
<figure class="wp-block-image aligncenter"><img src="https://via.placeholder.com/200x60/4A9B7F/FFFFFF?text=LOGO" alt="Logo" style="width:200px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Hi {{contact.first_name}},</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="has-text-align-center">Ready for Your First Win?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Today, we're sharing 3 quick-start tips that will set you up for success:</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul>
<li><strong>Tip #1:</strong> Complete your profile for personalized recommendations</li>
<li><strong>Tip #2:</strong> Bookmark our resource hub for easy access</li>
<li><strong>Tip #3:</strong> Join our community forum to connect with others</li>
</ul>
<!-- /wp:list -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"width":50} -->
<div class="wp-block-button has-custom-width wp-block-button__width-50"><a class="wp-block-button__link" href="{$short_url}">Access Resources</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
HTML
			,
		],
		[
			'subject' => 'Day 3: Unlock Advanced Features 🔓',
			'delay'   => 48, // 2 days
			'body'    => <<<HTML
<!-- wp:image {"align":"center","width":"200px"} -->
<figure class="wp-block-image aligncenter"><img src="https://via.placeholder.com/200x60/4A9B7F/FFFFFF?text=LOGO" alt="Logo" style="width:200px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Hi {{contact.first_name}},</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="has-text-align-center">Time to Level Up!</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">You've mastered the basics. Now let's explore the advanced features that will take you to the next level.</p>
<!-- /wp:paragraph -->

<!-- wp:columns -->
<div class="wp-block-columns">
<!-- wp:column -->
<div class="wp-block-column">
<h3>⚡ Power Tools</h3>
<p>Automation features to save you time</p>
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<h3>📊 Analytics</h3>
<p>Track your progress with detailed insights</p>
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<h3>🎯 Integrations</h3>
<p>Connect with your favorite tools</p>
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"width":50} -->
<div class="wp-block-button has-custom-width wp-block-button__width-50"><a class="wp-block-button__link" href="{$short_url}">Explore Features</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
HTML
			,
		],
		[
			'subject' => 'Day 4: Success Stories & Best Practices 💡',
			'delay'   => 72, // 3 days
			'body'    => <<<HTML
<!-- wp:image {"align":"center","width":"200px"} -->
<figure class="wp-block-image aligncenter"><img src="https://via.placeholder.com/200x60/4A9B7F/FFFFFF?text=LOGO" alt="Logo" style="width:200px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Hi {{contact.first_name}},</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="has-text-align-center">Learn From The Best</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">See how successful users are achieving amazing results with these proven strategies.</p>
<!-- /wp:paragraph -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><p>"This platform completely transformed how we work. We've saved 10+ hours per week!"</p><cite>— Sarah J., Marketing Manager</cite></blockquote>
<!-- /wp:quote -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><p>"The automation features are a game-changer. Highly recommend!"</p><cite>— Mike T., Business Owner</cite></blockquote>
<!-- /wp:quote -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"width":50} -->
<div class="wp-block-button has-custom-width wp-block-button__width-50"><a class="wp-block-button__link" href="{$short_url}">Read More Stories</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
HTML
			,
		],
		[
			'subject' => 'Day 5: Your Action Plan + Exclusive Bonus 🎁',
			'delay'   => 96, // 4 days
			'body'    => <<<HTML
<!-- wp:image {"align":"center","width":"200px"} -->
<figure class="wp-block-image aligncenter"><img src="https://via.placeholder.com/200x60/4A9B7F/FFFFFF?text=LOGO" alt="Logo" style="width:200px"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Hi {{contact.first_name}},</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="has-text-align-center">You're All Set! Here's Your Bonus</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Congratulations on completing the welcome series! As a thank you, here's an exclusive bonus to help you succeed.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"20px","right":"20px","bottom":"20px","left":"20px"}},"color":{"background":"#f0f9ff"}}} -->
<div class="wp-block-group has-background" style="background-color:#f0f9ff;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px">
<!-- wp:heading {"textAlign":"center","level":3} -->
<h3 class="has-text-align-center">🎁 EXCLUSIVE BONUS</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><strong>30-Minute Strategy Session</strong><br>Book a free consultation with our team</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"width":50} -->
<div class="wp-block-button has-custom-width wp-block-button__width-50"><a class="wp-block-button__link" href="{$short_url}">Claim Your Bonus</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"align":"center","fontSize":"small"} -->
<p class="has-text-align-center has-small-font-size"><a href="##crm.unsubscribe_url##">Unsubscribe</a> | <a href="##crm.manage_subscription_url##">Manage Preferences</a></p>
<!-- /wp:paragraph -->
HTML
			,
		],
	];

	$created_emails = [];

	foreach ( $emails as $i => $email ) {
		echo '   ' . ( $i + 1 ) . ". Adding: \"{$email['subject']}\" (Day " . ( $i + 1 ) . ")\n";

		$sequence_email = \FluentCampaign\App\Models\SequenceMail::create([
			'parent_id'  => $sequence->id,
			'type'       => 'sequence_mail',
			'title'      => 'Email #' . ( $i + 1 ),
			'subject'    => $email['subject'],
			'email_body' => $email['body'],
			'delay'      => $email['delay'],
			'delay_unit' => 'hours',
			'status'     => 'published',
			'created_by' => get_current_user_id(),
		]);

		$created_emails[] = $sequence_email;
		echo "      ✓ Email ID: {$sequence_email->id}, Delay: {$sequence_email->delay} hours\n";
	}

	echo "\n" . str_repeat( '=', 70 ) . "\n";
	echo "\n🎉 SUCCESS! Complete Sequence Created\n\n";
	echo "📊 Summary:\n";
	echo "   Sequence ID: {$sequence->id}\n";
	echo "   Smart Link ID: {$smart_link->id}\n";
	echo "   Short URL: {$short_url}\n";
	echo '   Total Emails: ' . count( $created_emails ) . "\n";
	echo "   Status: Published & Ready\n";
	echo "\n📧 Email Schedule:\n";

	foreach ( $created_emails as $i => $email ) {
		$day_num = $i + 1;
		echo "   Day {$day_num}: {$emails[$i]['subject']}\n";
	}

	echo "\n✅ Your sequence is ready to enroll subscribers!\n";
	echo "   Use MCP tool: fluentcrm/add-subscriber-to-sequence\n";
	echo "   With sequence_id: {$sequence->id}\n\n";
} catch ( \Exception $e ) {
	echo "\n❌ Error: " . $e->getMessage() . "\n";
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		echo $e->getTraceAsString() . "\n";
	}
	exit( 1 );
}
