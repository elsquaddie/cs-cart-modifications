{** Hook for displaying the popup on the homepage **}

{if $show_homepage_popup && $homepage_popup_banners}
    {style src="addons/homepage_popup/styles.css"}
    {script src="js/addons/homepage_popup/func.js"}

    <div id="homepage_popup_overlay" style="display:none;"></div>
    <div id="homepage_popup_wrapper"
         style="display:block; width: {$addons.homepage_popup.popup_width|default:600}px; height: {$addons.homepage_popup.popup_height|default:400}px;"
         data-dimming-enabled="{$addons.homepage_popup.enable_background_dimming}">

        <button id="close_homepage_popup" type="button">{__("homepage_popup.close_button")}</button>

        <div id="homepage_popup_slider_container">
            <div class="hp-slides">
                {foreach from=$homepage_popup_banners item="banner" name="banner_loop"}
                    <div class="hp-slide" {if !$smarty.foreach.banner_loop.first}style="display:none;"{/if}> {* Show only the first slide initially *}
                        <div class="hp-slide-content-area">
                            {if $banner.title}
                                <h2>{$banner.title}</h2>
                            {/if}

                            {if $banner.main_pair.image_path}
                                <div class="popup_image_container">
                                    <img src="{$banner.main_pair.image_path}" alt="{$banner.main_pair.alt|default:$banner.title|escape:html}">
                                </div>
                            {/if}

                            {if $banner.content}
                                <div class="popup_text_content">
                                    {$banner.content nofilter}
                                </div>
                            {/if}
                        </div>
                    </div>
                {/foreach}
            </div>

            {if count($homepage_popup_banners) > 1}
                <div class="hp-slider-nav">
                    <button class="hp-prev" type="button">{__("homepage_popup.previous_slide")}</button>
                    <button class="hp-next" type="button">{__("homepage_popup.next_slide")}</button>
                </div>
                <div class="hp-slider-dots" style="text-align:center; margin-top:10px;">
                    {foreach from=$homepage_popup_banners item="banner" name="dot_loop"}
                        <span class="hp-dot {if $smarty.foreach.dot_loop.first}active{/if}" data-slide-index="{$smarty.foreach.dot_loop.iteration - 1}"></span>
                    {/foreach}
                </div>
            {/if}
        </div>
    </div>
{/if}
