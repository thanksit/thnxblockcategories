<div class="form-group">
	<label class="control-label col-lg-3">
		<span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="{l s='In the default theme, these images will be displayed in the top horizontal menu; but only if the category is one of the first level (see Top horizontal menu module for more info).' mod='thnxblockcategories'}">
			{l s='Menu thumbnails' mod='thnxblockcategories'}
		</span>
	</label>
	<div class="col-lg-4">
		{$helper}
	</div>
	<div class="col-lg-6 col-lg-offset-3">
		<div class="help-block">{l s='Recommended dimensions (for the default theme): %1spx x %2spx' sprintf=[$format.width, $format.height]}</div>
	</div>
</div>