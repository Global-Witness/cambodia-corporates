<script>
	import { browser } from '$app/environment';
	import DisclaimerCopy from '../components/DisclaimerCopy.svelte';
	import Nav from '../components/Nav.svelte';

	// Get a Cookie
	function getCookie(cName) {
		if (!browser) return;
		const name = cName + '=';
		const cDecoded = decodeURIComponent(document.cookie); //to be careful
		const cArr = cDecoded.split('; ');
		let res;
		cArr.forEach((val) => {
			if (val.indexOf(name) === 0) res = val.substring(name.length);
		});
		return res;
	}

	// Set a Cookie
	function setCookie(cName, cValue, expDays) {
		if (!browser) return;
		let date = new Date();
		date.setTime(date.getTime() + expDays * 24 * 60 * 60 * 1000);
		const expires = 'expires=' + date.toUTCString();
		document.cookie = cName + '=' + cValue + '; ' + expires + '; path=/';
	}

	let showDisclaimer =
		getCookie('cambodian-companies-disclaimer-accepted') === 'yes' ? false : true;

	const acceptDisclaimer = () => {
		showDisclaimer = false;
		setCookie('cambodian-companies-disclaimer-accepted', 'yes', 300);
	};
</script>

<div>
	<Nav />
	<hr />
	<div id="top"></div>
	<div class="container" id="wrapper">
		{#if showDisclaimer}
			<div class="row" style="display: none; display: {showDisclaimer ? '' : 'none'}">
				<div class="col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2 col-xs-12">
					<DisclaimerCopy />
					<button class="btn btn-primary btn-block" on:click={acceptDisclaimer}>
						I understand and wish to proceed.</button
					>
				</div>
			</div>
		{/if}
		<slot />
	</div>
</div>
