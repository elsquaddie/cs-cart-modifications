{capture name="mainbox"}
    <form action="{""|fn_url}" method="post" name="homepage_popup_banners_form">
        <div id="pagination_contents"> {*This div is important for AJAX updates from pagination/check_items*}
        {include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id="pagination_contents"}

        {if $banners}
            <div class="table-responsive-wrapper">
                <table width="100%" class="table table-middle table-objects table-striped">
                <thead class="cm-first-sibling">
                <tr>
                    <th width="1%" class="left cm-no-hide-input">
                        {include file="common/check_items.tpl" check_statuses=fn_get_default_status_filters('', true) status_target_id="pagination_contents"}</th>
                    <th width="40%">{__("title")}</th>
                    <th width="10%">{__("position_short")}</th>
                    <th width="10%" class="center">{__("status")}</th>
                    <th width="10%">&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$banners item=banner}
                <tr class="cm-row-status-{$banner.status|lower}">
                    <td class="left cm-no-hide-input">
                        <input type="checkbox" name="banner_ids[]" value="{$banner.banner_id}" class="cm-item" /></td>
                    <td>
                        <a href="{"homepage_popup_banners.update?banner_id=`$banner.banner_id`"|fn_url}">{$banner.title}</a>
                    </td>
                    <td>{$banner.position}</td>
                    <td class="center">
                        {include file="common/select_popup.tpl" id=$banner.banner_id status=$banner.status object_id_name="banner_id" table="homepage_popup_banners" popup_additional_class="" hide_for_vendor=true display="text"}
                    </td>
                    <td class="nowrap right">
                        {capture name="tools_list"}
                            <li>{btn type="list" text=__("edit") href="homepage_popup_banners.update?banner_id=`$banner.banner_id`"}</li>
                            <li>{btn type="list" text=__("delete") class="cm-confirm" href="homepage_popup_banners.delete?banner_id=`$banner.banner_id`" method="POST"}</li>
                        {/capture}
                        <div class="hidden-tools">
                            {dropdown content=$smarty.capture.tools_list}
                        </div>
                    </td>
                </tr>
                {/foreach}
                </tbody>
                </table>
            </div>
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}

        {include file="common/pagination.tpl" div_id="pagination_contents"}
        </div> {* End pagination_contents div *}
    </form>
{/capture}

{capture name="buttons"}
    {capture name="tools_list"}
        <li>{btn type="delete_selected" dispatch="dispatch[homepage_popup_banners.m_delete]" form="homepage_popup_banners_form"}</li>
    {/capture}
    {dropdown content=$smarty.capture.tools_list}
    {btn type="add" text=__("homepage_popup.add_banner_button") href="homepage_popup_banners.update"}
{/capture}

{include file="common/mainbox.tpl" title=__("homepage_popup.manage_banners_title") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons}
