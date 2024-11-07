<script lang="ts">
	import { debounce, sortBy, flatten, values } from 'lodash/fp';

	import * as D from '../../lib/db/frontend';
	import * as Q from '../../lib/db/queries';
	import HelpPanel from './HelpPanel.svelte';
	import { defaultTo } from 'lodash';

	let urlParams = new URLSearchParams(window.location.search);

	let params = Object.fromEntries(urlParams);

	let search = {
		type: params.type,
		individual: params.individual,
		threshold: params.threshold,
		nationality: params.nationality,
		house: params.house,
		street: params.street,
		company: params.company,
		id: params.id,
		search: params.search
	};

	const searchButtonClicked = () => {
		params = search;
	};

	let db;

	$: D.eventual.then((d) => (db = d));

	// $: searchResultCompanies = null ? Q.searchCompany(term, db) : null;

	const defaultResult = {
		people: [],
		amendments: [],
		registrations: [],
		companies: [],
		addresses: []
	};

	const doSearch = () => {
		if (!db) {
			alert('db not loaded!');
			return;
		}

		switch (search.type) {
			case 'company':
				return Q.companySearch(search.company, db);
			case 'address':
				return Q.addressSearch(search.house, search.street, db);
			case 'ids':
				return Q.idSearch(search.id, db);
			case 'individual':
				return Q.nameSearch(search.individual, null, search.threshold, db) || defaultResult;
			case 'nationality':
				return Q.nameSearch('', search.nationality, null, db) || defaultResult;
			default:
				return defaultResult;
		}
	};

	$: otherResult = db && params ? { ...defaultResult, ...doSearch() } : defaultResult;

	export let data = {};
	export let type = '';
	export let individual = '';

	function printFlag(nationality) {
		const recon = {
			'United-States': 'American',
			Cambodia: 'Cambodian',
			China: 'Chinese',
			France: 'French',
			Japan: 'Japanese',
			'South-Korea': 'Korean',
			Malaysia: 'Malaysian',
			Singapore: 'Singaporean',
			Thailand: 'Thai',
			Vietnam: 'Vietnamese'
		};

		const flags = Object.fromEntries(Object.entries(recon).map(([key, value]) => [value, key]));

		if (nationality in flags) {
			return `<img class="flag flag-icon" src="/assets/img/flags/${flags[nationality]}.png" alt="${nationality}" data-toggle="tooltip" data-placement="top" title="${nationality}">`;
		} else {
			nationality = nationality || 'Unknown';
			return `<i class="fa fa-globe" data-toggle="tooltip" data-placement="top" title="${nationality}"><span class="sr-only">${nationality} </span></i>`;
		}
	}

	$: hasExactMatch = () => {
		return otherResult?.people?.some((p) => p[2].name.toLowerCase() === individual.toLowerCase());
	};

	$: exactMatches = otherResult?.people?.filter(
		(p) => p[2].name.toLowerCase() === individual.toLowerCase()
	);

	$: similarMatches = otherResult?.people?.filter(
		(p) => p[2].name.toLowerCase() !== individual.toLowerCase()
	);

	const lawFirms = [
		'No.33, St.294 corner 29, Sangkat Tonle Bassac, Khan Chamcarmorn, Phnom Penh',
		'No.56, St.Sothearours, Sangkat Chak Tomuk, Khan Daun Penh, Phnom Penh',
		'No.24, St.462, Sangkat Tonle Bassac, Khan Chamcarmorn, Phnom Penh'
	];

	// Law firm tooltip HTML
	const lawFirmText = `<span class="address-asterisk" title="Please note that this is the address of a law firm which conducts company registrations. Therefore the companies registered to this address are not necessarily connected."><i class="fa fa-asterisk"></i></span>`;

	// Helper to check if an address is a law firm
	function isLawFirm(address) {
		return lawFirms.includes(address);
	}

	function formatDate(dateStr) {
		if (!dateStr) return 'N.A.';
		const date = new Date(dateStr);
		return date.toLocaleDateString('en-US', {
			weekday: 'short',
			day: '2-digit',
			month: 'long',
			year: 'numeric'
		});
	}

	/** parse ids to find out collection date **/
	function checkId(string) {
		const parsed = { R: [], A: [] };
		const parts = Array.isArray(string) ? string : string.toString().split(',');

		parts.forEach((part) => {
			if (part[0] === 'R') {
				parsed.R.push(part.substring(1));
			} else {
				parsed.A.push(part.substring(1));
			}
		});

		return parsed;
	}

	// Helper to create chairperson links
	function createChairpersonLinks(chairmen) {
		return chairmen
			.map(
				(chairman) =>
					`<a target="_self" href="/search?type=individual&individual=${encodeURIComponent(chairman)}">${chairman}</a>`
			)
			.join(', ');
	}

	// Helper to create ID links with appropriate labels
	function createIdLinks(ids) {
		return ids
			.map(
				(id) =>
					`<span class="id-label">${
						id[0] === 'R' ? 'Registration' : id[0] === 'A' ? 'Amendment' : ''
					}</span> <a target="_self" href="/search?type=ids&id=${id}">#${id}</a>`
			)
			.join(' / ');
	}

	function checkDataCollected(id) {
		const checkIds = checkId(id);
		if (
			(checkIds.R && Math.max(...checkIds.R) > 31917) ||
			(checkIds.A && Math.max(...checkIds.A) > 9873)
		) {
			return 'data collected Feb 2016';
		}
		return 'data collected Dec 2014';
	}

	function generateLinks(names) {
		return names
			.map(
				(name) =>
					`<a class="individual-link" target="_self" href="/search?type=individual&individual=${encodeURIComponent(name)}">${name}</a>`
			)
			.join(', ');
	}

	$: hasResults = flatten(values(otherResult)).length > 0;
</script>

<!-- @DEBUG: {JSON.stringify(params, null, 2)}, {JSON.stringify(search, null, 2)} -->

<section class="row" id="search">
	<form id="form-search" class="form-switch" method="get" action="/search">
		<div class="col-xs-12 col-sm-2 col-md-2">
			<div class="form-group">
				<label class="sr-only" for="type">Search By:</label>
				<select
					bind:value={search.type}
					class="form-control selectpicker form-switch switcher"
					id="type"
					name="type"
					title="Search by..."
				>
					<option value={undefined}>Search By...</option>
					<option value="individual">Person</option>
					<option value="nationality">Nationality of Chairperson</option>
					<option value="address">Street address and/or house number</option>
					<option value="company">Company</option>
					<option value="ids">Registration &amp; Amendent IDs</option>
				</select>
			</div>
		</div>

		<div class="col-xs-12 col-sm-7 col-md-8">
			{#if search.type == undefined}
				<div class="form-group default">
					<p class="form-control-static"><strong>Please</strong> select a search filter.</p>
				</div>
			{/if}

			{#if search.type == 'individual'}
				<div class="form-group individual">
					<label for="individual" class="sr-only"> Individual </label>
					<div class="form-group name">
						<input
							on:input={(e) => {
								search.individual = e.target.value;
							}}
							bind:value={search.individual}
							type="text"
							name="individual"
							id="individual"
							class="form-control"
							placeholder="Individual..."
						/>
					</div>
					<div class="form-group fuzz">
						<select
							bind:value={search.threshold}
							name="threshold"
							id="fuzziness"
							class="form-control selectpicker"
							title="Fuzziness..."
						>
							<option value="0">Exact match</option>

							<option value="parts" data-subtext="when the search term is contained within a word">
								Partial match
							</option>

							<option value="1">Up to 1 character difference</option>
							<option value="2">Up to 2 characters difference</option>
							<option value="3">Up to 3 characters difference</option>
							<option value="4">Up to 4 characters difference</option>
						</select>
					</div>
				</div>
			{/if}

			{#if search.type == 'nationality'}
				<div class="form-group nationality">
					<label for="nationality" class="sr-only"> Nationality </label>
					<div class="input-group">
						<select
							name="nationality"
							id="nationality"
							bind:value={search.nationality}
							class="form-control selectpicker"
							title="Select nationality..."
							data-live-search="true"
							data-live-search-placeholder="Search for a nationality..."
						>
							<optgroup label="Most Present">
								<option value="American">American</option>
								<option value="Cambodian">Cambodian</option>
								<option value="Chinese">Chinese</option>
								<option value="French">French</option>
								<option value="Japanese">Japanese</option>
								<option value="Korean">Korean</option>
								<option value="Malaysian">Malaysian</option>
								<option value="Singaporean">Singaporean</option>
								<option value="Thai">Thai</option>
								<option value="Vietnamese">Vietnamese</option>
							</optgroup>
							<optgroup label="Other Present Nationalities">
								<option value="African">African</option><option value="Arabian">Arabian</option>
								<option value="Argentinian">Argentinian</option>
								<option value="Australian">Australian</option>
								<option value="Austrian">Austrian</option>
								<option value="Bangladeshi">Bangladeshi</option>
								<option value="Belarusian">Belarusian</option>
								<option value="Belgian">Belgian</option>
								<option value="Brazilian">Brazilian</option>
								<option value="British">British</option>
								<option value="Bruneian">Bruneian</option>
								<option value="Bulgarian">Bulgarian</option>
								<option value="Burkinabe">Burkinabe</option>
								<option value="Burmese / Myanma">Burmese / Myanma</option>
								<option value="Cameroonian">Cameroonian</option>
								<option value="Canadian">Canadian</option>
								<option value="Chilean">Chilean</option>
								<option value="Costa Rican">Costa Rican</option>
								<option value="Croatian">Croatian</option>
								<option value="Czech">Czech</option>
								<option value="Danish">Danish</option>
								<option value="Dutch">Dutch</option>
								<option value="Ecuadorian">Ecuadorian</option>
								<option value="Egyptian">Egyptian</option>
								<option value="English">English</option>
								<option value="Estonian">Estonian</option>
								<option value="Finish">Finish</option>
								<option value="Georgian">Georgian</option>
								<option value="German">German</option>
								<option value="Ghanaian">Ghanaian</option>
								<option value="Hungarian">Hungarian</option>
								<option value="Indian">Indian</option>
								<option value="Indonesian">Indonesian</option>
								<option value="Iranian">Iranian</option>
								<option value="Iraqi">Iraqi</option>
								<option value="Irish">Irish</option>
								<option value="Israeli">Israeli</option>
								<option value="Italian">Italian</option>
								<option value="Jordanian">Jordanian</option>
								<option value="Kazakhstani">Kazakhstani</option>
								<option value="Kuwaiti">Kuwaiti</option>
								<option value="Lao">Lao</option>
								<option value="Lebanese">Lebanese</option>
								<option value="Mauritian">Mauritian</option>
								<option value="Mexican">Mexican</option>
								<option value="Moldovan">Moldovan</option>
								<option value="Moroccan">Moroccan</option>
								<option value="Mozambican">Mozambican</option>
								<option value="Nepali">Nepali</option>
								<option value="New Zealander">New Zealander</option>
								<option value="Nigerian">Nigerian</option>
								<option value="Norwegian">Norwegian</option>
								<option value="Pakistani">Pakistani</option>
								<option value="Peruvian">Peruvian</option>
								<option value="Philippine">Philippine</option>
								<option value="Polish">Polish</option>
								<option value="Portuguese">Portuguese</option>
								<option value="Romanian">Romanian</option>
								<option value="Russian">Russian</option>
								<option value="Sierra Leonean">Sierra Leonean</option>
								<option value="Slovakian">Slovakian</option>
								<option value="South African">South African</option>
								<option value="Spanish">Spanish</option>
								<option value="Sri Lankan">Sri Lankan</option>
								<option value="Sudanese">Sudanese</option>
								<option value="Swedish">Swedish</option>
								<option value="Swiss">Swiss</option>
								<option value="Taiwanese">Taiwanese</option>
								<option value="Tajikistani">Tajikistani</option>
								<option value="Turkish">Turkish</option>
								<option value="Ugandan">Ugandan</option>
								<option value="Ukrainian">Ukrainian</option>
								<option value="Unknown">Unknown</option>
								<option value="Uzbekistan">Uzbekistan</option>
								<option value="Venezuelan">Venezuelan</option>
								<option value="Yemeni">Yemeni</option>
							</optgroup>
						</select>
						<span
							class="input-group-addon"
							data-toggle="tooltip"
							data-placement="bottom"
							title="This list was extracted directly from the Ministry of Commerce’s dataset and has not been altered."
						>
							<i class="fa fa-question-circle"></i>
						</span>
					</div>
				</div>
			{/if}

			{#if search.type == 'address'}
				<div class="form-group address">
					<div class="form-group house">
						<input
							bind:value={search.house}
							class="form-control"
							type="text"
							name="house"
							placeholder="House # (optional)"
						/>
					</div>
					<div class="form-group street">
						<input
							bind:value={search.street}
							class="form-control"
							type="text"
							name="street"
							placeholder="Street name"
						/>
					</div>
				</div>
			{/if}

			{#if search.type == 'company'}
				<div class="form-group company">
					<label for="company" class="sr-only"> Search key </label>
					<input
						type="text"
						name="company"
						bind:value={search.company}
						id="company"
						class="form-control"
						placeholder="Company name..."
					/>
				</div>
			{/if}

			{#if search.type == 'ids'}
				<div class="form-group ids">
					<label for="ids" class="sr-only"> Search key </label>
					<input
						bind:value={search.id}
						type="text"
						name="id"
						id="id"
						class="form-control"
						placeholder="Registration or Amendment ID..."
					/>
				</div>
			{/if}
		</div>

		<div class="col-xs-12 col-sm-3 col-md-2">
			<div class="form-group">
				<input type="hidden" name="search" value="submitted" />
				<button
					type="submit"
					on:click={() => searchButtonClicked()}
					class="btn btn-primary btn-block"
				>
					<i class="fa fa-search"></i>
					Search
				</button>
			</div>
		</div>
	</form>
</section>

{#if params.type && db}
	<ul class="nav nav-pills" role="tablist">
		{#if otherResult?.registrations?.length > 0}
			<li role="presentation">
				<a href="#registrations" aria-controls="home" role="tab" data-toggle="pill">
					Registrations [{otherResult?.registrations?.length}]
				</a>
			</li>
		{/if}
		{#if otherResult?.addresses?.length > 0}
			<li role="presentation" class="active">
				<a href="#addresses" aria-controls="home" role="tab" data-toggle="pill">
					Addresses [{otherResult?.addresses?.length}]
				</a>
			</li>
		{/if}

		{#if otherResult?.people?.length > 0}
			<li role="presentation">
				<a href="#individuals" aria-controls="home" role="tab" data-toggle="pill">
					Individuals [{otherResult?.people?.length}]
				</a>
			</li>
		{/if}

		{#if otherResult?.companies?.length > 0}
			<li role="presentation">
				<a href="#companies" aria-controls="home" role="tab" data-toggle="pill">
					Companies [{otherResult?.companies?.length}]
				</a>
			</li>
		{/if}

		{#if otherResult?.amendments?.length > 0}
			<li role="presentation">
				<a href="#amendments" aria-controls="profile" role="tab" data-toggle="pill">
					Amendments [{otherResult?.amendments?.length || 0}]
				</a>
			</li>
		{/if}

		<li role="presentation" class={`pull-right ${!hasResults ? 'active' : ''}`}>
			<a href="#section-help" class="help-tab" aria-controls="profile" role="tab" data-toggle="pill"
				>Help</a
			>
		</li>
	</ul>

	<div class="tab-content">
		<div role="tabpanel" class={`tab-pane ${!hasResults ? 'active' : ''}`} id="section-help">
			{#if search.search === 'submitted' && !hasResults}
				<div class="alert alert-nodata">
					<h2>
						<i class="fa fa-exclamation-triangle"></i> <strong>No Results</strong> for the related search.
					</h2>
				</div>
			{/if}
			<HelpPanel bind:t={search.type} />
		</div>

		<div
			role="tabpanel"
			class={`tab-pane ${otherResult?.addresses?.length > 0 ? 'active' : ''}`}
			id="addresses"
		>
			{#if otherResult?.addresses?.length > 0}
				{#each otherResult?.addresses as a (a.address)}
					<div class="card">
						<h3>
							<span class="entry-name">
								{a.address}
								{#if isLawFirm(a.address)}
									{@html lawFirmText}
								{/if}
							</span>
							<small>
								<a target="_self" href={`/search?type=ids&id=${a.hits.join(',')}`}>
									[{a.hits.length}
									{a.hits.length > 1 ? 'hits' : 'hit'}]
								</a>
							</small>
						</h3>

						<h4 class="label-title">Companies registered to this address</h4>
						<ul>
							{#each sortBy('company', a.entries) as e}
								<li>
									<h5>
										<a target="_self" href={`/search?type=ids&id=${e.registration}`}>{e.company}</a>
									</h5>
									<div class="row">
										<!-- Chairpersons Section -->
										{#if e.chairmen && e.chairmen.length > 0}
											<div class="col-md-6">
												<strong>Chairperson{e.chairmen.length > 1 ? 's' : ''}: </strong>
												{@html generateLinks(e.chairmen)}
											</div>
										{/if}

										<!-- Agents Section -->
										{#if e.agents && e.agents[0]}
											<div class="col-md-6">
												<strong>Agent{e.agents.length > 1 ? 's' : ''}: </strong>
												{@html generateLinks(e.agents)}
											</div>
										{/if}
									</div>
								</li>
							{/each}
						</ul>
					</div>
				{/each}
			{:else}
				<div class="alert alert-nodata">
					<h2>
						<i class="fa fa-exclamation-triangle"></i> <strong>No Addresses</strong> for the related
						search.
					</h2>
				</div>
			{/if}
		</div>

		<div
			role="tabpanel"
			class={`tab-pane ${otherResult?.companies?.length > 0 ? 'active' : ''}`}
			id="companies"
		>
			{#if otherResult?.companies?.length > 0}
				{#each otherResult?.companies as c, i}
					<div class="card">
						<span class="chairmen">
							{c.chairmen.length > 1 ? 'Chairpersons: ' : 'Chairperson: '}
							{@html createChairpersonLinks(c.chairmen)}
						</span>

						<h3>
							<span class="entry-name">{c.name}</span>
							<small>
								<a targe="_self" href={`/search?type=ids&id=${c.ids.join(',')}`}>
									[ {c.ids.length}
									{c.ids.length > 1 ? 'hits' : 'hit'} ]
								</a>
							</small>
						</h3>

						<!-- Render IDs with Labels -->
						<div class="id-links">
							{@html createIdLinks(c.ids.sort())}
						</div>

						<!-- Data Collection Label based on Check ID Result -->
						{#if checkId(c.ids).R.some((id) => id > 31917) || checkId(c.ids).A.some((id) => id > 9873)}
							<span class="small-label">data collected Feb 2016</span>
						{:else}
							<span class="small-label">data collected Dec 2014</span>
						{/if}
					</div>
				{/each}
			{:else}
				<div class="alert alert-nodata">
					<h2>
						<i class="fa fa-exclamation-triangle"></i> <strong>No Companies</strong> for the related
						search.
					</h2>
				</div>
			{/if}
		</div>

		<div
			role="tabpanel"
			class={`tab-pane ${otherResult?.registrations?.length > 0 ? 'active' : ''}`}
			id="registrations"
		>
			{#if otherResult?.registrations.length > 0}
				{#each otherResult?.registrations as r}
					<div class="card">
						<h3>
							<span class="entry-name">{r.name}</span>
							<small>#R{r.id}</small>
						</h3>

						{#if r.address}
							<p>
								<strong>Company Address:</strong>
								{#if r.house && r.street}
									<a
										target="_self"
										href={`/search?type=address&house=${encodeURIComponent(r.house)}&street=${encodeURIComponent(r.street)}`}
										title="click here for all companies registered to this address"
									>
										{r.address}
									</a>
									{isLawFirm(r.address) ? lawFirmText : ''}
								{:else}
									{r.address} {isLawFirm(r.address) ? lawFirmText : ''}
								{/if}
							</p>
						{/if}

						{#if r.telephone}
							<p><i class="fa fa-phone"></i> {r.telephone}</p>
						{/if}

						{#if r.email}
							<p><i class="fa fa-envelope"></i> {r.email}</p>
						{/if}

						<p>
							<span class="chairperson-title"><strong>Chairperson</strong></span>
							{r.chairman_gender}
							<a
								target="_self"
								href={`/search?type=individual&individual=${encodeURIComponent(r.chairman)}`}
								>{r.chairman}</a
							>
							{@html printFlag(r.chairman_nationality)}
							<br />
							<strong>Chairperson Address:</strong>
							{#if r.chairman_address_house && r.chairman_address_street}
								<a
									target="_self"
									href={`/search?type=address&house=${encodeURIComponent(r.chairman_address_house)}&street=${encodeURIComponent(r.chairman_address_street)}`}
									title="click here for all companies registered to this address"
								>
									{r.chairman_address}
								</a>
							{:else}
								{r.chairman_address}
							{/if}
						</p>

						{#if r.agent && r.chairman !== r.agent}
							<p>
								<span class="chairperson-title"><strong>Agent</strong></span>
								{r.agent_gender}
								<a
									target="_self"
									href={`/search?type=individual&individual=${encodeURIComponent(r.agent)}`}
									>{r.agent}</a
								>
								[{r.agent_position}]
							</p>
						{/if}

						<!-- Check the ID and display data collection date -->
						{#if checkId('R' + r.id).R?.[0] > 31917 || checkId('R' + r.id).A?.[0] > 9873}
							<span class="small-label">data collected Feb 2016</span>
						{:else}
							<span class="small-label">data collected Dec 2014</span>
						{/if}
					</div>
				{/each}
			{:else}
				<div class="alert alert-nodata">
					<h2>
						<i class="fa fa-exclamation-triangle"></i> <strong>No Registrations</strong> for the related
						search.
					</h2>
				</div>
			{/if}
		</div>

		<div
			role="tabpanel"
			class={`tab-pane ${otherResult?.amendments?.length > 0 ? 'active' : ''}`}
			id="amendments"
		>
			{#if otherResult.amendments && otherResult.amendments.length > 0}
				{#each otherResult.amendments as p, i}
					<div class="card">
						<strong>Resolution date:</strong>
						<span class="date">{formatDate(p.resolution_date)}</span>
						<h3>
							{p.name}
							<small
								><a target="_self" href={`/search?type=ids&id=${p.id}`}>Amendment {p.id}</a></small
							>
						</h3>
						<p>{@html p.resolution_text}</p>
						<strong>Chairperson: </strong>
						{p?.chairman?.gender || p.chairman_gender || p.chairperson_gender}
						<a
							class="individual-link"
							target="_self"
							href="/search?type=individual&individual={encodeURIComponent(
								p?.chairman?.name || p.chairman || p.chairperson
							)}"
						>
							{p?.chairman?.name || p.chairman || p.chairperson}
						</a>
						<span class="small-label">{checkDataCollected(p.id)}</span>
					</div>
				{/each}
			{:else}
				<div class="alert alert-nodata">
					<h2>
						<i class="fa fa-exclamation-triangle"></i> <strong>No Amendments</strong> for the related
						search.
					</h2>
				</div>
			{/if}
		</div>

		<div
			role="tabpanel"
			class={`tab-pane ${otherResult?.people?.length > 0 ? 'active' : ''}`}
			id="individuals"
		>
			{#if otherResult && otherResult?.people?.length > 0}
				{#if search.type === 'individual' && hasExactMatch()}
					<h3>Exact Match</h3>
					{#each exactMatches as p, i}
						<div class="card exact-match">
							<h3>
								<span class="entry-name">{p[2].gender} {p[2].name}</span>
								{@html printFlag(p[2].nationality)}
								<small>
									<a target="_self" href={`/search?type=ids&id=${p[2].ids}`}
										>[ {p[2].nb} {p[2].nb > 1 ? 'hits' : 'hit'} ]</a
									>
								</small>
							</h3>
							<ul>
								{#each p[2].companies as company}
									<li>
										<a target="_self" href={`/search?type=ids&id=${company.registration}`}>
											{@html company.name}
										</a>
									</li>
								{/each}
							</ul>
						</div>
					{/each}
				{/if}

				{#if search.type === 'individual' && similarMatches.length > 0}
					<div class="alert alert-info">
						<i class="fa fa-exclamation-circle"></i> Names with similar spellings to your search
						terms are shown below. They <strong>may or may not</strong> refer to the same person.
					</div>
				{/if}

				{#each similarMatches as p, i}
					<div class="card">
						<h3>
							<span class="entry-name">
								{@html p[2].gender}
								{@html p[2].name}
							</span>
							{@html printFlag(p[2].nationality)}
							<small>
								<a target="_self" href={`/search?type=ids&id=${p[2].ids}`}>
									[ {p[2].nb}
									{p[2].nb > 1 ? 'hits' : 'hit'} ]
								</a>
							</small>
						</h3>
						<ul>
							{#each p[2].companies as company}
								<li>
									<a target="_self" href={`/search?type=ids&id=${company.registration}`}>
										{@html company.name}
									</a>
								</li>
							{/each}
						</ul>
					</div>
				{/each}
			{:else}
				<div class="alert alert-nodata">
					<h2>
						<i class="fa fa-exclamation-triangle"></i> <strong>No Individuals</strong> for the related
						search.
					</h2>
				</div>
			{/if}
		</div>
	</div>
{:else if params.type}
	<div>Loading</div>
{:else}
	<h1 class="sr-only">Global Witness’ Cambodia Corporates</h1>

	<p class="text-center">
		Global Witness’ Cambodia Corporates site is a publically accessible, fully-searchable database
		containing information on company ownership. A powerful investigative tool used by Global
		Witness, it provides users with new ways to research companies and individuals of interest,
		identify connections between them and gain insights into corporate ownership in Cambodia.
	</p>

	<p class="text-center">
		Cambodia Corporates is a complete mirror of the Ministry of Commerce’s registration and
		amendment datasets as of February 2016. It is comprised of <strong
			>22,808 company registration entries</strong
		>
		and <strong>7,502 amendments</strong> and contains pieces of information no longer published by the
		Cambodian government.
	</p>

	<p class="text-center">
		Cambodia Corporates was launched in June 2016 to enable citizens, journalists, companies and
		investors to securely and easily find out who owns, controls or has major stakes in companies in
		Cambodia.
	</p>
	<p><br /><br /><br /></p>
	<div class="row">
		<div class="col-md-5 col-md-offset-1 col-xs-12 col-sm-6">
			<a target="_self" href="/project" class="btn btn-primary btn-block">More about the Database</a
			>
		</div>
		<div class="col-md-5 col-xs-12 col-sm-6">
			<a
				href="https://www.globalwitness.org/en-gb/campaigns/cambodia/#more"
				target="_blank"
				class="btn btn-primary btn-block">Global Witness in Cambodia</a
			>
		</div>
	</div>
{/if}
