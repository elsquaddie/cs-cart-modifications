{if $show_customizable_homepage_popup}
    {assign var="popup_settings" value=$addons.customizable_homepage_popup}

    <div id="customizable_homepage_popup_overlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 9998;"></div>

    <div id="customizable_homepage_popup_wrapper" style="display:none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; background-color: #fff; padding: 20px; box-shadow: 0 0 15px rgba(0,0,0,0.3);"
         data-ca-popup-width="{$popup_settings.popup_width|default:"600px"}"
         data-ca-popup-height="{$popup_settings.popup_height|default:"400px"}"
         data-ca-popup-delay="{$popup_settings.delay|default:0}"
         data-ca-popup-animation="{$popup_settings.animation|default:"none"}"
         data-ca-popup-frequency="{$popup_settings.frequency|default:"once_per_session"}"
         data-ca-dimming-enabled="{$popup_settings.enable_background_dimming|default:"Y"}"
         data-show-on-load="{if $show_customizable_homepage_popup}Y{else}N{/if}"
    >
        <button id="close_customizable_homepage_popup" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>

        {if $customizable_homepage_popup_banners}
            <div id="customizable_homepage_popup_slider_container">
                {foreach from=$customizable_homepage_popup_banners item="banner" name="popup_banners_loop"}
                    <div class="hp-slide" {if !$smarty.foreach.popup_banners_loop.first}style="display:none;"{/if}>
                        {if $banner.title}<h3>{$banner.title}</h3>{/if}
                        {if $banner.main_pair.image_path}
                            <img src="{$banner.main_pair.image_path}" alt="{$banner.title|escape:html}" style="max-width: 100%; height: auto;" />
                        {/if}
                        <div>{$banner.content nofilter}</div> {* nofilter because it's WYSIWYG content *}
                    </div>
                {/foreach}

                {if $customizable_homepage_popup_banners|count > 1}
                    <div class="hp-slider-nav" style="text-align: center; margin-top: 10px;">
                        <button class="hp-prev" type="button">&lt; {__("previous")}</button>
                        <button class="hp-next" type="button">{__("next")} &gt;</button>
                    </div>
                    <div class="hp-slider-dots" style="text-align: center; margin-top: 5px;">
                        {foreach from=$customizable_homepage_popup_banners item="banner" name="popup_dots_loop"}
                            <span class="hp-dot" data-slide-index="{$smarty.foreach.popup_dots_loop.index}" style="cursor: pointer; height: 10px; width: 10px; margin: 0 2px; background-color: #bbb; border-radius: 50%; display: inline-block;"></span>
                        {/foreach}
                    </div>
                {/if}
            </div>
        {else}
            <p>{__("customizable_homepage_popup.no_active_banners")}</p>
        {/if}
    </div>
{/if}
