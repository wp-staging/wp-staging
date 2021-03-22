<?php
/**
 * @var string $urlPublic
 */
?>
<div class="wpstg--modal--backup--import--configure">
    <div class="wpstg--modal--backup--import--configure-review-backup">
        Reviewing backup.
    </div>
    <!-- DATABASE SEARCH AND REPLACE -->
    <div class="wpstg--modal--backup--import--search-replace--wrapper">
        <a href="#" class="wpstg--tab--toggle" data-target=".wpstg--import--database-search-and-replace" style="text-decoration: none;">
            <span style="margin-right: .25em">►</span>
            <?php esc_html_e('Database Search and Replace', 'wp-staging') ?>
        </a>

        <div class="wpstg--import--database-search-and-replace" style="display:none; padding-left: .75em;">
            <div class="wpstg--modal--backup--import--search-replace--info">
                <p><?php esc_html_e('You can do a search-and-replace of the values of the database being imported. This is optional, as WPSTAGING already takes care of replacing the Site URL and ABSPATH for you. You should avoid doing replacements of generic strings, as this could replace unwanted values and cause issues in the database.', 'wp-staging') ?></p>
            </div>
            <div class="wpstg--modal--backup--import--search-replace--input--container">
                <div class="wpstg--modal--backup--import--search-replace--input-group">
                    <input name="wpstg__backup__import__search[{i}]" data-index="{i}" class="wpstg--backup--import--search" placeholder="Search"/>
                    <input name="wpstg__backup__import__replace[{i}]" data-index="{i}" class="wpstg--backup--import--replace" placeholder="Replace"/>
                    <button class="wpstg--import--advanced-options--button wpstg--modal--backup--import--search-replace--remove"><?php esc_html_e('-', 'wp-staging') ?></button>
                </div>
            </div>
            <div class="wpstg--modal--backup--import--search-replace--new--wrapper">
                <button class="wpstg--import--advanced-options--button wpstg--modal--backup--import--search-replace--new"><?php esc_html_e('+', 'wp-staging') ?></button>
            </div>
        </div>
    </div>
</div>
