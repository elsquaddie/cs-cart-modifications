{capture name="mainbox"}
    <form action="{""|fn_url}" method="post" name="homepage_popup_banner_update_form" enctype="multipart/form-data" class="form-horizontal form-edit">
        <input type="hidden" name="banner_id" value="{$banner_data.banner_id|default:0}" />

        <div class="tabs cm-j-tabs">
            <ul class="nav nav-tabs">
                <li id="tab_general_banner" class="cm-js active"><a>{__("general")}</a></li>
            </ul>
        </div>

        <div class="cm-tabs-content" id="content_tab_general_banner">
            <fieldset>
                <div class="control-group">
                    <label class="control-label cm-required" for="elm_banner_title">{__("title")}:</label>
                    <div class="controls">
                        <input type="text" name="banner_data[title]" id="elm_banner_title" value="{$banner_data.title}" size="50" class="input-large" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="elm_banner_content">{__("content")}:</label>
                    <div class="controls">
                        <textarea name="banner_data[content]" id="elm_banner_content" cols="50" rows="8" class="cm-wysiwyg input-large">{$banner_data.content}</textarea>
                    </div>
                </div>

                {include file="common/image_uploader.tpl" image_name="new_banner_image" image_object_type="homepage_popup_banner" image_pair=$banner_data.main_pair image_object_id=$banner_data.banner_id|default:0 image_title=__("image")}

                <div class="control-group">
                    <label class="control-label" for="elm_banner_position">{__("position_short")}:</label>
                    <div class="controls">
                        <input type="text" name="banner_data[position]" id="elm_banner_position" value="{$banner_data.position|default:0}" size="10" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="elm_banner_status">{__("status")}:</label>
                    <div class="controls">
                        <select name="banner_data[status]" id="elm_banner_status">
                            <option value="A" {if $banner_data.status == "A"}selected="selected"{/if}>{__("active")}</option>
                            <option value="D" {if $banner_data.status == "D"}selected="selected"{/if}>{__("disabled")}</option>
                        </select>
                    </div>
                </div>
            </fieldset>
        </div>

        {assign var="but_role" value="submit-link"}
        {assign var="but_name" value="dispatch[homepage_popup_banners.update]"}
        {assign var="but_target_form" value="homepage_popup_banner_update_form"}
        {capture name="buttons_block"}
            {include file="buttons/save_cancel.tpl" but_name=$but_name cancel_action="close" save=$banner_data.banner_id}
        {/capture}
    </form>
{/capture}
{include file="common/mainbox.tpl" title=($banner_data.banner_id ? $banner_data.title : __("homepage_popup.add_banner_button")) content=$smarty.capture.mainbox buttons=$smarty.capture.buttons_block}
