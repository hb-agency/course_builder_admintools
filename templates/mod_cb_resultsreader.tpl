<div class="<?php echo $this->class; ?> block"<?php echo $this->cssID; ?><?php if ($this->style): ?> style="<?php echo $this->style; ?>"<?php endif; ?>>
<?php if ($this->headline): ?>

<<?php echo $this->hl; ?>><?php echo $this->headline; ?></<?php echo $this->hl; ?>>
<?php endif; ?>

<h2><?php echo $this->course; ?><br />
<?php echo $this->user; ?>
</h2>

<table cellpadding="0" cellspacing="0" border="0" class="ce_table">
	<thead>
		<tr>
			<th><?php echo $this->hdate; ?></th>
			<th><?php echo $this->hstatus; ?></th>
			<th><?php echo $this->hscore; ?></th>
			<th><?php echo $this->hcert; ?></th>
		</tr>
	</thead>
	<tbody>
	<?php foreach($this->results as $result): ?>
		<tr>
			<td><?php echo $result['date']; ?></td>
			<td><?php echo $result['status']; ?></td>
			<td><?php echo $result['score']; ?></td>
			<td><?php echo $result['cert']; ?></td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>

</div>