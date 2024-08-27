<?php

use WPStaging\Framework\Settings\DarkMode;

$defaultColorMode = get_option(DarkMode::OPTION_DEFAULT_COLOR_MODE, '');
?>

<div class="wpstg-color-mode-container">
    <input id="wpstg-system-mode-button" type="radio" name="colorModeOptions" class="wpstg-dark-mode-input" value="system" <?php echo ($defaultColorMode === 'system') ? 'checked' : '';?> >
    <div class="wpstg-system-mode-container <?php echo ($defaultColorMode === 'system') ? 'active' : '';?>">
        <label for="wpstg-system-mode-button" class="wpstg-system-mode-title" data-title="OS Default Mode">
            <svg version="1.1" class="wpstg-system-mode" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="30px" height="20px" viewBox="0 0 512 512" xml:space="preserve">
                <g stroke-width="0"></g>
                <g stroke-linecap="round" stroke-linejoin="round"></g>
                <g>
                    <path class="st0" d="M459.078,72.188H52.922v249.5h406.156V72.188z M419.172,281.766H92.844V112.109h326.328V281.766z"></path>
                    <path class="st0" d="M452.438,351.641H59.578L0,407.609v32.203h512v-32.203L452.438,351.641z M205.188,402.422l9.766-15.625h82.094 l9.781,15.625H205.188z"></path>
                </g>
            </svg>
        </label>
    </div>

    <input id="wpstg-light-mode-button" type="radio" name="colorModeOptions" class="wpstg-dark-mode-input" value="light" <?php echo ($defaultColorMode === 'light') ? 'checked' : '';?>>
    <div class="wpstg-light-mode-container <?php echo ($defaultColorMode === 'light') ? 'active' : '';?>">
        <label for="wpstg-light-mode-button" class="wpstg-light-mode-title" data-title="Light Mode">
            <svg class="wpstg-light-mode" width="30px" height="20px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <g stroke-width="0"></g>
                <g stroke-linecap="round" stroke-linejoin="round"></g>
                <g>
                    <path d="M17 12C17 14.7614 14.7614 17 12 17C9.23858 17 7 14.7614 7 12C7 9.23858 9.23858 7 12 7C14.7614 7 17 9.23858 17 12Z"></path>
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 1.25C12.4142 1.25 12.75 1.58579 12.75 2V4C12.75 4.41421 12.4142 4.75 12 4.75C11.5858 4.75 11.25 4.41421 11.25 4V2C11.25 1.58579 11.5858 1.25 12 1.25ZM3.66865 3.71609C3.94815 3.41039 4.42255 3.38915 4.72825 3.66865L6.95026 5.70024C7.25596 5.97974 7.2772 6.45413 6.9977 6.75983C6.7182 7.06553 6.2438 7.08677 5.9381 6.80727L3.71609 4.77569C3.41039 4.49619 3.38915 4.02179 3.66865 3.71609ZM20.3314 3.71609C20.6109 4.02179 20.5896 4.49619 20.2839 4.77569L18.0619 6.80727C17.7562 7.08677 17.2818 7.06553 17.0023 6.75983C16.7228 6.45413 16.744 5.97974 17.0497 5.70024L19.2718 3.66865C19.5775 3.38915 20.0518 3.41039 20.3314 3.71609ZM1.25 12C1.25 11.5858 1.58579 11.25 2 11.25H4C4.41421 11.25 4.75 11.5858 4.75 12C4.75 12.4142 4.41421 12.75 4 12.75H2C1.58579 12.75 1.25 12.4142 1.25 12ZM19.25 12C19.25 11.5858 19.5858 11.25 20 11.25H22C22.4142 11.25 22.75 11.5858 22.75 12C22.75 12.4142 22.4142 12.75 22 12.75H20C19.5858 12.75 19.25 12.4142 19.25 12ZM17.0255 17.0252C17.3184 16.7323 17.7933 16.7323 18.0862 17.0252L20.3082 19.2475C20.6011 19.5404 20.601 20.0153 20.3081 20.3082C20.0152 20.6011 19.5403 20.601 19.2475 20.3081L17.0255 18.0858C16.7326 17.7929 16.7326 17.3181 17.0255 17.0252ZM6.97467 17.0253C7.26756 17.3182 7.26756 17.7931 6.97467 18.086L4.75244 20.3082C4.45955 20.6011 3.98468 20.6011 3.69178 20.3082C3.39889 20.0153 3.39889 19.5404 3.69178 19.2476L5.91401 17.0253C6.2069 16.7324 6.68177 16.7324 6.97467 17.0253ZM12 19.25C12.4142 19.25 12.75 19.5858 12.75 20V22C12.75 22.4142 12.4142 22.75 12 22.75C11.5858 22.75 11.25 22.4142 11.25 22V20C11.25 19.5858 11.5858 19.25 12 19.25Z"></path>
                </g>
            </svg>
        </label>
    </div>

    <input id="wpstg-dark-mode-button" type="radio" name="colorModeOptions" class="wpstg-dark-mode-input" value="dark" <?php echo ($defaultColorMode === 'dark') ? 'checked' : '';?>>
    <div class="wpstg-dark-mode-container <?php echo ($defaultColorMode === 'dark') ? 'active' : '';?>">
        <label for="wpstg-dark-mode-button" class="wpstg-dark-mode-title" data-title="Dark Mode">
            <svg class="wpstg-dark-mode" width="30px" height="20px" viewBox="0 0 24 24"  xmlns="http://www.w3.org/2000/svg">
                <g stroke-width="0"></g>
                <g stroke-linecap="round" stroke-linejoin="round"></g>
                <g>
                    <path d="M12 22C17.5228 22 22 17.5228 22 12C22 11.5373 21.3065 11.4608 21.0672 11.8568C19.9289 13.7406 17.8615 15 15.5 15C11.9101 15 9 12.0899 9 8.5C9 6.13845 10.2594 4.07105 12.1432 2.93276C12.5392 2.69347 12.4627 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z"></path>
                </g>
            </svg>
        </label>
    </div>
</div>
