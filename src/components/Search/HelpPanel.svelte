<script>
	export let t;
</script>

{#if t === 'individual'}
	<h2>Searching for Individuals</h2>
	<p>
		Cambodia Corporates enables the user to search for a person’s name contained within the dataset.
		It can search company registration records (which generally only list the chairperson’s name),
		and amendments to those records, which sometimes also give the names of shareholders and
		directors.
	</p>
	<p>
		Importantly, it allows the user to search for the name of a person in a number of different ways
		including “fuzzy search” options (<a
			href="https://en.wikipedia.org/wiki/Approximate_string_matching"
			target="_blank">https://en.wikipedia.org/wiki/Approximate_string_matching</a
		>) that return results that are similar, if not exactly the same. This works in the same way as
		search engines such as Google, in that when you search for a particular word it brings up other
		words that are similarly spelt. This is particularly important for Cambodia Corporates for two
		reasons:
	</p>
	<ol>
		<li>
			The way the data has been inputted into the Ministry of Commerce’s corporate registry is
			inconsistent. This means that the same person’s name can be spelt numerous different ways.
		</li>
		<li>
			Cambodian names are not originally written in the Roman alphabet and the spelling of
			transliterations varies.
		</li>
	</ol>
	<p>
		The search option chosen will affect the results received so it may be worth trying more than
		one in order to obtain the best results. The default search option is set to ‘Up to 2 characters
		difference’ which Global Witness has found produces the best results.
	</p>

	<p>The search options are:</p>
	<ul>
		<li>
			Exact match – this returns only those results that match words within the field of that
			database exactly. For example, a Person search for “Dara Borey” would only return entries for
			“Mr Dara Borey”. Please note that partial matches (see below) will also be returned under any
			exact match results and under a warning banner.
		</li>
		<li>
			Partial match – this returns any entries that contain the search terms anywhere in the text.
			For example, a search for “van” would return the entries for any name which includes this
			combination of letters including “Chhan Sovanna”, “Duong Vannak” and “Vanneth Nary”.
		</li>
		<li>
			Up to 1 characters difference – when the user wants to receive results that differ from the
			search term by 1 character.
		</li>
		<li>
			Up to 2 characters difference – when the user wants to receive results that differ from the
			search term by 2 characters.
		</li>
		<li>
			Up to 3 characters difference – when the user wants to receive results that differ from the
			search term by 3 characters.
		</li>
		<li>
			Up to 4 characters difference – when the user wants to receive results that differ from the
			search term by 4 characters.
		</li>
	</ul>
{:else if t === 'nationality'}
	<h2>Searching by Nationality</h2>
	<p>
		Cambodia Corporates allows users to search by the nationality of the company chairperson. This
		is the key piece of information that was not included in the Ministry of Commerce’s updated
		corporate registry that was launched in April 2015.
	</p>
	<p>
		Of the two types of data stored on Cambodia Corporates (company registrations and amendments),
		only company registration records list the nationality of the chairperson. This information is
		included in most but not all company registration records. Amendments do not provide this
		information.
	</p>
	<p>
		Searching by nationality of chairperson is a great way to find leads, particularly for users
		looking for links to international companies. Although Cambodia Corporates only lists companies
		registered in Cambodia, it is common for international companies operating in the country to set
		up local Cambodian subsidiaries with an individual from the home nation acting as chairperson.
		Therefore, the results of a ‘Nationality of chairperson’ search brings up multiple leads for
		companies that potentially have strong international links. For example, a journalist interested
		in French companies operating in Cambodia can begin by searching for all companies that list a
		French chairperson before moving on to research each company further.
	</p>
	<p>
		Global Witness has mirrored exactly the list of nationalities stored on the previous Ministry of
		Commerce corporate registry without making any changes. As a result, the nationalities listed
		are inconsistent and some are even geographically incorrect. For example, the original
		government data listed both ‘African’ and ‘Arabian’ as nationalities.
	</p>
{:else if t === 'address'}
	<h2>Searching for Addresses</h2>
	<p>
		Often the best way of revealing links between companies and individuals can be through
		identifying shared registered addresses. However, addresses in Cambodia are unpredictable and
		sometimes, for example, the same street will have more than one building of the same number or
		listed addresses won’t actually exist. Coupled with inconsistent input of the original data,
		this makes the address search function on Cambodia Corporates useful but sometimes unreliable.
	</p>
	<p>
		Cambodia Corporates enables users to search for a specific address within company registration
		records and amendments. It searches both the registered addresses of companies and that of
		company chairs.
	</p>
	<p>
		Users can search using a house number and/or a street name or number. It is not possible to
		search by name of neighbourhood, village, city or province. In fact, the vast majority of
		companies are registered in Phnom Penh and Cambodia’s other urban centres.
	</p>
	<p>
		The majority of Cambodia’s urban streets are numbered. However, when a road is named the search
		function works best if the user just searches for the key terms and omits all other words.
	</p>

	<p>For example:</p>
	<ul>
		<li>
			If a company is registered at ‘No. 500, Preah Monivong Boulevard, Sangkat Tonle Bassac, Khan
			Chamcarmorn, Phnom Penh’ then it is best to put the number ‘3’ in the search box labelled
			‘House #’ and just type ‘Monivong’ into the ‘Street name’ search box.
		</li>
		<li>
			If a company is registered at ‘No.500, St.Sothearours, Sangkat Chak Tomuk, Khan Daun Penh,
			Phnom Penh’ then it is best to put the number ‘500’ in the search box labelled ‘House #’ and
			just type ‘Sothearours’ into the ‘Street name’ search box.
		</li>
	</ul>
	<p>
		Due to the inconsistency in the way that words like “street” or “boulevard” are spelled and
		abbreviated, the database ignores some of these terms when searching. This can lead to
		unexpected results if you search for “st.” or “corner” which appear in addresses but will not
		return any results. Therefore, you should keep your address search terms to the unique
		components of the street name.
	</p>
	<p>
		Users of Cambodia Corporates should be aware that results are skewed for a number of addresses
		in Phnom Penh. These addressees belong to law firms that conduct registrations on behalf of
		companies. This means that all companies registered by the law firm in question are shown as
		being registered to the same address. This is misleading as it gives the false impression that
		the listed registered companies are all connected when in fact they aren't necessarily.
	</p>
	<p>
		The following 3 addresses belong to law firms that provide a company registration service to
		numerous companies:
	</p>
	<ul>
		<li>No.33, St.294 corner 29, Sangkat Tonle Bassac, Khan Chamcarmorn, Phnom Penh</li>
		<li>No.56, St.Sothearours, Sangkat Chak Tomuk, Khan Daun Penh, Phnom Penh</li>
		<li>No.24, St.462, Sangkat Tonle Bassac, Khan Chamcarmorn, Phnom Penh</li>
	</ul>

	<p>
		If you are aware of other addresses on Cambodia Corporates that belong to law firms that conduct
		company registrations then please email: <a href="mailto:cambodiacorporates@globalwitness.org"
			>cambodiacorporates@globalwitness.org</a
		> and we will add them to the list.
	</p>
{:else if t === 'company'}
	<h2>Searching for Companies</h2>
	<p>
		The data in Cambodia Corporates can be searched using the company name. Users can search both
		company registration records and amendments for company names. Searches can be conducted using
		the full name of a company or part of the name. For example, if the user searches for the
		company name ‘Royal’ then all companies which include this word will be provided as results.
	</p>
	<p>
		When searching, the company name must be spelt correctly as the company search function is
		unable to conduct “fuzzy searches” (<a
			href="https://en.wikipedia.org/wiki/Approximate_string_matching"
			target="_blank">https://en.wikipedia.org/wiki/Approximate_string_matching</a
		>). For example, searching for the company “Cambodia Gold” will bring up all company
		registration and amendment records that mention this exact term. However, searching for
		‘Cambodia Gld’ (missing the letter ‘o’) will yield no results if the spelling is incorrect.
	</p>
	<p>
		If an initial company search does not bring up any results then it is worth trying again with a
		slightly altered spelling. Like with the names of individuals, this is particularly important in
		Cambodia where company names tend not to be originally written in the Roman alphabet and
		therefore spellings can vary. In addition, the way the original data was inputted into the
		Ministry of Commerce’s corporate registry was inconsistent. This means that the same company’s
		name can be spelt numerous different ways.
	</p>
{:else if t === 'ids'}
	<h2>Searching for IDs</h2>
	<p>
		The amendment and registration ID numbers contained within Cambodia Corporates come directly
		from the Ministry of Commerce’s corporate registry. They are however not related to a company’s
		formal registration number but instead describe an internal ID number the Ministry of Commerce’s
		corporate registry assigns to each record it contains. Company registration records are assigned
		an ID number beginning with an ‘R’ whilst amendment ID numbers start with an ‘A’.
	</p>
	<p>
		On Cambodia Corporates, users need only type in the number of the specific registration record
		or amendment, leaving off the letter. Where a registration record and an amendment share a
		number, both results will be provided to the user.
	</p>
	<p>
		The main purpose of this search function is to enable users to easily share findings. For
		example, if a user is looking at a number of specific records and wants to share these with a
		contact, the easiest way is to send over the registration and/or amendment ID numbers. It is
		also possible to share the link to the records as each registration and amendment generates its
		own unique URL.
	</p>
{:else}{/if}
