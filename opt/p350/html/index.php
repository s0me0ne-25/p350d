<?php
	// Longest message the form accepts, in characters
	define('MAX_MESSAGE_CHARS', 300);

	$pageClass = 'page_user';
	$pageScripts = ['/p350_user.js'];
	$pageSubtitle = 'Девочка тинейджер позвони на пейджер! :-P';
	require_once __DIR__ . '/inc_header.php';
?>
<h3 id="status"<?php
	if (isset($_POST['txdata'])) {
		// Count characters, not bytes, and a line break as one character the
		// way the browser's maxlength does (the form submits it as CRLF).
		// preg_match_all() fails on invalid UTF-8, which no browser sends.
		$msgChars = preg_match_all('/./su', str_replace("\r\n", "\n", $_POST['txdata']));
		if ($msgChars === false) {
			?> class="err">Ошибка отправки: текст сообщения не в UTF-8</h3> <?php
		} else if ($msgChars > MAX_MESSAGE_CHARS) {
			?> class="err">Ошибка отправки: сообщение длиннее <?php echo MAX_MESSAGE_CHARS; ?> знаков</h3> <?php
		} else if (pagerdCommand('page ' . $_POST['txdata'], false, $error) === false) {
			// pagerd transmits before it replies, so the reply is not awaited
			?> class="err">Ошибка отправки: <?php echo htmlspecialchars($error); ?></h3> <?php
		} else {
			?> class="ok">Отправлено!</h3> <?php
		}
	} else {
		?> class="hide">holder</h3><?php
	}
?>

<form action="/" method="POST" class="pager_body" onsubmit="return checksubmit()">
	<table>
		<tbody>
			<tr>
				<td colspan="2" class="label">СООБЩЕНИЕ</td>
			</tr>
			<tr valign="bottom">
				<td><textarea id="text" name="txdata" rows="8" maxlength="<?php echo MAX_MESSAGE_CHARS; ?>" class="pager_display"></textarea></td>
				<td><button type="submit" class="send_btn" id="send_btn">&#9654;</button></td>
	</tr>
	</tbody></table>
</form>
<?php require_once __DIR__ . '/inc_footer.php'; ?>
