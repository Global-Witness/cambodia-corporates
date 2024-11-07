<script>
	// turn off SSR for just _this_ component, use only the component when in browser.
	import { browser } from '$app/environment';

	const ComponentConstructor = browser
		? import('./SearchSub.svelte').then((module) => {
				return module.default;
			})
		: new Promise(() => {});
</script>

<div>
	{#await ComponentConstructor}
		<p>Loading...</p>
	{:then component}
		<svelte:component this={component} />
		<!--{:catch error}
		<p>Something went wrong: {error.message}</p>-->
	{/await}
</div>
