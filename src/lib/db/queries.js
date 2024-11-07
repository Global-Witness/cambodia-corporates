import levenshtein from 'js-levenshtein';

const rowToObj = (row) => {
	const transformedrow = row.values.map((r) =>
		Object.fromEntries(row.columns.map((col, index) => [col, r[index]]))
	);
	return transformedrow[0];
};

export const companies = (db) => {
	var stmt = db.prepare('SELECT * from companies');
	let res = [];
	while (stmt.step()) {
		// prints out 4 users in console
		var row = stmt.getAsObject();
		res.push(row);
	}

	return res;
};

export const searchCompany = (term, db) => {
	var stmt = db.prepare(`SELECT * from companies WHERE name LIKE '%${term}%'`);
	let res = [];
	while (stmt.step()) {
		// prints out 4 users in console
		var row = stmt.getAsObject();
		res.push(row);
	}
	return res;
};

export const companyById = (id, db) => {
	const stmt = db.prepare(`SELECT * FROM companies WHERE id IN (${id})`);
	let res = [];
	while (stmt.step()) {
		// prints out 4 users in console
		var row = stmt.getAsObject();
		res.push(row);
	}
	return res[0];
};

export function nameSearch(term, nationality, threshold, db) {
	const results = {};
	const amendments = [];

	const _name = term;
	const _nationality = nationality;
	const _threshold = threshold || 2;

	let companies_where = '';
	let amendments_where = '';
	let check = '';

	let companies_ids = [];
	let amendments_ids = [];

	if (_name !== '') {
		check = _name; //_name.charAt(0).toUpperCase() + _name.slice(1).toLowerCase();
		const normalized_name = _name.toLowerCase();

		if (_threshold === 'parts') {
			const placeholder_name_spaces = `% ${normalized_name} %`;
			const placeholder_name_nospaces = `%${normalized_name}%`;
			const placeholder_name_spaces_front = `% ${normalized_name}%`;
			const placeholder_name_spaces_back = `%${normalized_name} %`;

			const companies_sql = `
                SELECT id, chairman, agent FROM companies WHERE
                LOWER(chairman) LIKE ? OR
                LOWER(agent) LIKE ? OR
                LOWER(chairman) LIKE ? OR
                LOWER(agent) LIKE ?`;
			const companies_stmt = db.prepare(companies_sql);
			companies_stmt.bind([
				placeholder_name_spaces,
				placeholder_name_spaces_front,
				placeholder_name_nospaces,
				placeholder_name_spaces_back
			]);
			while (companies_stmt.step()) {
				const row = companies_stmt.getAsObject();
				companies_ids.push(row.id);
			}

			const amendments_sql = `
                SELECT id, chairman FROM amendments WHERE
                LOWER(chairman) LIKE ? OR
                LOWER(chairman) LIKE ?`;
			const amendments_stmt = db.prepare(amendments_sql);
			amendments_stmt.bind([placeholder_name_spaces, placeholder_name_nospaces]);

			while (amendments_stmt.step()) {
				const row = amendments_stmt.getAsObject();
				amendments_ids.push(row.id);
			}
		} else {
			const companies_sql = `SELECT id, chairman, agent FROM companies`;
			const companies_result = db.exec(companies_sql)[0];
			companies_result.values.forEach((row) => {
				if (
					levenshtein(check, row[1]) <= _threshold ||
					(row[2] !== '' && levenshtein(check, row[2]) < _threshold)
				) {
					companies_ids.push(row[0]);
				}
			});

			const amendments_sql = `SELECT id, chairman FROM amendments`;
			const amendments_result = db.exec(amendments_sql)[0];
			amendments_result.values.forEach((row) => {
				if (levenshtein(check, row[1]) <= _threshold) {
					amendments_ids.push(row[0]);
				}
			});
		}
	}

	if (companies_ids.length > 0) companies_where = `id IN (${companies_ids.join(',')})`;
	if (amendments_ids.length > 0) amendments_where = `id IN (${amendments_ids.join(',')})`;

	if (_nationality !== null) {
		if (companies_where !== '') {
			companies_where += ' AND ';
			amendments_where += ' AND ';
		}
		companies_where += 'chairman_nationality = ?';
		amendments_where += '1 = 2';
	}

	const hits = _nationality === '' ? [check] : [];

	if (check !== '' || _nationality !== '') {
		let rows;
		const s = `
            SELECT 
                count(id) as nb, 
                group_concat(id, ',') as ids, 
                chairman, 
                group_concat(chairman_gender, ',') as chairman_genders, 
                group_concat(name, '|') as names, 
                group_concat(agent, '|') as agents,
                group_concat(chairman_nationality, ',') as nationalities
            FROM (
                SELECT 
                    'R' || id as id, 
                    name, 
                    chairman, 
                    chairman_gender, 
                    chairman_nationality, 
                    agent   
                FROM companies 
                WHERE ${companies_where !== '' ? companies_where : '1 = 2'} 
                UNION SELECT 
                    'A' || id as id, 
                    name, 
                    chairman, 
                    chairman_gender, 
                    NULL as chairman_nationality,
                    NULL as agent 
                FROM amendments 
                WHERE ${amendments_where !== '' ? amendments_where : '1 = 2'}
            ) 
            GROUP BY chairman 
            ORDER BY nb DESC, chairman`;

		if (_nationality !== '') {
			const stmt = db.prepare(s);
			stmt.bind([_nationality]);
			rows = [];
			while (stmt.step()) {
				rows.push(stmt.getAsObject());
			}
		} else {
			rows = db.exec(s)[0].values.map((row) => ({
				nb: row[0],
				ids: row[1],
				chairman: row[2],
				chairman_genders: row[3],
				names: row[4],
				agents: row[5],
				nationalities: row[6]
			}));
		}

		const hitsbits = [];
		for (const row of rows) {
			let hit = 0;
			let lev = 0;
			let sim = 0;

			if (check !== '' && levenshtein(check, row.chairman) <= _threshold) {
				lev = levenshtein(check, row.chairman);
				sim = similar_text(check, row.chairman);

				hits.push(row.chairman);
				hit = 1;
			} else {
				const agents = row.agents?.split('|') || [];
				for (const a of agents) {
					if (check !== '' && levenshtein(check, a) < _threshold) {
						lev = levenshtein(check, a);
						sim = similar_text(check, a);

						hits.push(a);
						hit = 2;
					}
				}
			}

			const genders = row.chairman_genders.split(',').reduce((acc, gender) => {
				acc[gender] = (acc[gender] || 0) + 1;
				return acc;
			}, {});
			row.chairman_gender = Object.keys(genders).reduce((a, b) =>
				genders[a] > genders[b] ? a : b
			);

			try {
				row.chairman_nationality = row.nationalities.split(',').sort().reverse()[0];
			} catch (e) {}

			const chairman = {
				gender: row.chairman_gender,
				name: hit === 1 || check === '' ? `<strong>${row.chairman}</strong>` : row.chairman,
				ids: row.ids,
				nb: row.nb,
				nationality: row.chairman_nationality
			};

			const ids = row.ids.split(',');
			const names = row.names.split('|');
			const agentsArray = row.agents?.split('|') || [];
			const hitsname = [];
			const lines = [];
			for (let i = 0; i < names.length; i++) {
				if (!hitsname.includes(names[i])) {
					hitsname.push(names[i]);
					lines.push([
						names[i],
						ids[i],
						`<span class="details">${names[i]}${hit === 2 && levenshtein(check, agentsArray[i]) < 3 ? ` (Agent: <b>${agentsArray[i]}</b>)` : ''}</span>`
					]);
				} else {
					const hitarray = hitsname.indexOf(names[i]);
					lines[hitarray][1] += `,${ids[i]}`;
				}
			}

			lines.sort((a, b) => a[0].localeCompare(b[0]));

			chairman.companies = lines.map((l) => ({ name: l[2], registration: l[1] }));
			hitsbits.push([lev, hit, chairman]);
		}

		if (check !== '') hitsbits.sort((a, b) => a[0] - b[0]);

		results.people = hitsbits;

		const resolutions_where =
			hits.length > 0
				? `resolution_text LIKE '%${hits.join("%' OR resolution_text LIKE '%")}%'`
				: '';

		if (resolutions_where !== '') {
			const amendments_sql = `
                SELECT 
                    'A' || id as id, 
                    name, 
                    resolution_date, 
                    resolution_text, 
                    chairman, 
                    chairman_gender 
                FROM amendments 
                WHERE ${resolutions_where} 
                ORDER BY name, resolution_date`;
			const amendments_result = db.exec(amendments_sql)[0];

			amendments_result?.values.forEach((row) => {
				row[3] = row[3]
					.replace(/-\s*res/gi, '<br>- Res')
					.replace(/\+\s*res/gi, '<br>- Res')
					.replace(/([0-9]+)\.\s*res/gi, '<br>- Res');
				const amendment = {
					id: row[0],
					name: row[1],
					resolution_date: row[2],
					resolution_text: row[3],
					chairperson: row[4],
					chairperson_gender: row[5]
				};
				if (hits.length < 100) {
					const rere = new RegExp(hits.filter((x) => x.length > 1).join(','), 'gi');
					amendment.resolution_text = amendment.resolution_text.replace(rere, function (match) {
						return '<span class="highlight">' + match + '</span>';
					});

					/* 
					hits.forEach((h) => {
						const hlen = h.length;
						const rlen = `<span class="highlight">${h}</span>`.length;

						let cpos = 0;
						let pos = amendment.resolution_text.toLowerCase().indexOf(h.toLowerCase(), cpos);

						const rere = new RegExp(h, 'gi');

						amendment.resolution_text = amendment.resolution_text.replace(rere, function (match) {
							return '<span class="highlight">' + match + '</span>';
						});

						/* 			while (
							(pos = amendment.resolution_text.toLowerCase().indexOf(h.toLowerCase(), cpos)) !== -1
						) {
							amendment.resolution_text =
								amendment.resolution_text.substring(0, pos) +
								`<span class="highlight">
                                ${amendment.resolution_text.substring(pos, pos + hlen)}
                                </span>` +
								amendment.resolution_text.substring(pos + hlen);
							cpos = pos + rlen;
						} 
					}); */
				}
				amendments.push(amendment);
			});
		}
		results.amendments = amendments;
	}

	return results;
}

function similar_text(first, second) {
	if (first === second) {
		return 100;
	}
	if (!first.length || !second.length) {
		return 0;
	}

	let pos1 = 0,
		pos2 = 0,
		max = 0;
	const l = first.length;
	const t = second.length;

	for (let p = 0; p < l; p++) {
		for (let q = 0; q < t; q++) {
			let l = 0;
			while (p + l < l && q + l < t && first.charAt(p + l) === second.charAt(q + l)) {
				l++;
			}
			if (l > max) {
				max = l;
				pos1 = p;
				pos2 = q;
			}
		}
	}

	let sum = max;

	if (sum) {
		if (pos1 && pos2) {
			sum += similar_text(first.substr(0, pos1), second.substr(0, pos2));
		}
		if (pos1 + max < l && pos2 + max < t) {
			sum += similar_text(
				first.substr(pos1 + max, l - pos1 - max),
				second.substr(pos2 + max, t - pos2 - max)
			);
		}
	}

	return sum;
}

/** ID (Registration, Resolution, Amendment) Search **/
export function idSearch(id, db) {
	const idParam = id;

	let checkRegistration = '';
	let checkAmendment = '';

	const results = {
		registrations: [],
		amendments: []
	};

	if (idParam) {
		const ids = idParam.split(',');

		ids.forEach((i) => {
			if (i.startsWith('R')) {
				checkRegistration += (checkRegistration ? ',' : '') + i.substring(1);
			} else if (i.startsWith('A')) {
				checkAmendment += (checkAmendment ? ',' : '') + i.substring(1);
			} else {
				const intId = parseInt(i, 10);
				checkAmendment += (checkAmendment ? ',' : '') + intId;
				checkRegistration += (checkRegistration ? ',' : '') + intId;
			}
		});
	}

	if (db && idParam) {
		if (checkRegistration) {
			const regQuery = `SELECT * FROM companies WHERE id IN (${checkRegistration})`;
			const regResult = db.exec(regQuery);

			for (const row of regResult) {
				results.registrations.push(rowToObj(row));
			}
		}

		if (checkAmendment) {
			const amendQuery = `SELECT * FROM amendments WHERE id IN (${checkAmendment}) ORDER BY resolution_date`;
			const amendResult = db.exec(amendQuery);

			for (const _row of amendResult) {
				const row = rowToObj(_row);
				row.resolution_text = row.resolution_text
					.replace(/-\s*res/g, '<br>- Res')
					.replace(/\+\s*res/g, '<br>- Res')
					.replace(/([0-9]+)\.\s*res/g, '<br>- Res');

				results.amendments.push(row);
			}
		}
	}
	return results;
}

/** Address Search **/
export function addressSearch(house = '', street = '', db) {
	const results = [];

	let streetCheck = false;
	let houseCheck = false;
	let digitStreet = false;

	let check = '';
	let checkChairman = '';
	let checkResolutionPlace = '';
	let checkResolutionAddress = '';

	const placeholders = {};
	if (house) {
		houseCheck = true;
		placeholders.house_1 = `%${house}%`;
		placeholders.house_2 = `%${house}%`;
		placeholders.house_3 = `%${house}%`;
		placeholders.house_4 = `%${house}%`;

		check = 'house LIKE :house_1';
		checkChairman = 'chairman_address_house LIKE :house_2';
		checkResolutionPlace = 'resolution_place_house LIKE :house_3';
		checkResolutionAddress = 'resolution_address_house LIKE :house_4';
	}

	if (street) {
		streetCheck = true;
		digitStreet = /^\d+$/.test(street); // Check if street is a digit

		// Create street parameter variations
		placeholders.streetS1 = `%${street}%`;
		placeholders.streetSC1 = `%${street}%`;
		placeholders.streetSRP1 = `%${street}%`;
		placeholders.streetSRA1 = `%${street}%`;

		if (check) {
			check += ' AND ';
			checkChairman += ' AND ';
			checkResolutionPlace += ' AND ';
			checkResolutionAddress += ' AND ';
		}

		if (digitStreet) {
			// Exact number search for digit streets
			placeholders.street = `%${street}%`;
			placeholders.street2 = `${street} %`;
			placeholders.street3 = `% ${street} %`;
			placeholders.street4 = `% ${street}`;

			check +=
				'(street LIKE :street OR street LIKE :street2 OR street LIKE :street3 OR street LIKE :street4)';
			checkChairman +=
				'(chairman_address_street LIKE :streetC OR chairman_address_street LIKE :street2C OR chairman_address_street LIKE :street3C OR chairman_address_street LIKE :street4C)';
			checkResolutionPlace +=
				'(resolution_place_street LIKE :streetRP OR resolution_place_street LIKE :street2RP OR resolution_place_street LIKE :street3RP OR resolution_place_street LIKE :street4RP)';
			checkResolutionAddress +=
				'(resolution_address_street LIKE :streetRA OR resolution_address_street LIKE :street2RA OR resolution_address_street LIKE :street3RA OR resolution_address_street LIKE :street4RA)';
		} else {
			// Flexible string search for non-digit streets
			check += 'street LIKE :streetS1';
			checkChairman += 'chairman_address_street LIKE :streetSC1';
			checkResolutionPlace += 'resolution_place_street LIKE :streetSRP1';
			checkResolutionAddress += 'resolution_address_street LIKE :streetSRA1';
		}
	}

	// Only proceed if there are search conditions
	if (db && check) {
		const sql = `
      SELECT count(id) as nb,
             group_concat(id, ',') as ids,
             group_concat(name, ',') as names,
             group_concat(chairman, ',') as chairmans,
             group_concat(agent, ',') as agents,
             lower(ad) as ad
      FROM (
        SELECT concat('R',id) as id, name, chairman, agent, address as ad, house as hs, street as st, '0000-01-01' as da
        FROM companies WHERE ${check}
        UNION
        SELECT concat('R',id) as id, name, chairman, agent, chairman_address as ad, house as hs, street as st, '0000-01-01' as da
        FROM companies WHERE ${checkChairman}
        UNION
        SELECT concat('A',id) as id, name, chairman, '' as agent, resolution_place as ad, resolution_place_house as hs, resolution_place_street as st, COALESCE(resolution_date, '1000-01-01') as da
        FROM amendments WHERE ${checkResolutionPlace}
        UNION
        SELECT concat('A',id) as id, name, chairman, '' as agent, resolution_address as ad, resolution_address_house as hs, resolution_address_street as st, COALESCE(resolution_date, '1000-01-01') as da
        FROM amendments WHERE ${checkResolutionAddress}
        ORDER BY da
      ) as merged
      GROUP BY lower(ad)
      ORDER BY nb DESC`;

		const stmt = db.prepare(sql);

		let bindings = {};

		// Bind parameters for house
		if (houseCheck) {
			bindings = {
				...bindings,
				':house_1': placeholders.house_1,
				':house_2': placeholders.house_2,
				':house_3': placeholders.house_3,
				':house_4': placeholders.house_4
			};
			/* 	stmt.bind({
				':house_1': placeholders.house_1,
				':house_2': placeholders.house_2,
				':house_3': placeholders.house_3,
				':house_4': placeholders.house_4
			}); */
		}

		// Bind parameters for street
		if (streetCheck) {
			if (!digitStreet) {
				bindings = {
					...bindings,
					':streetS1': placeholders.streetS1,
					':streetSC1': placeholders.streetSC1,
					':streetSRP1': placeholders.streetSRP1,
					':streetSRA1': placeholders.streetSRA1
				};
				/* 	stmt.bind({
					':streetS1': placeholders.streetS1,
					':streetSC1': placeholders.streetSC1,
					':streetSRP1': placeholders.streetSRP1,
					':streetSRA1': placeholders.streetSRA1
				}); */
			} else {
				bindings = {
					...bindings,
					':street': placeholders.street,
					':street2': placeholders.street2,
					':street3': placeholders.street3,
					':street4': placeholders.street4,
					':streetC': placeholders.streetS1,
					':street2C': placeholders.street2,
					':street3C': placeholders.street3,
					':street4C': placeholders.street4,
					':streetRP': placeholders.streetS1,
					':street2RP': placeholders.street2,
					':street3RP': placeholders.street3,
					':street4RP': placeholders.street4,
					':streetRA': placeholders.streetS1,
					':street2RA': placeholders.street2,
					':street3RA': placeholders.street3,
					':street4RA': placeholders.street4
				};
				/* 			stmt.bind({
					':street': placeholders.street,
					':street2': placeholders.street2,
					':street3': placeholders.street3,
					':street4': placeholders.street4,
					':streetC': placeholders.streetS1,
					':street2C': placeholders.street2,
					':street3C': placeholders.street3,
					':street4C': placeholders.street4,
					':streetRP': placeholders.streetS1,
					':street2RP': placeholders.street2,
					':street3RP': placeholders.street3,
					':street4RP': placeholders.street4,
					':streetRA': placeholders.streetS1,
					':street2RA': placeholders.street2,
					':street3RA': placeholders.street3,
					':street4RA': placeholders.street4
				}); */
			}
		}

		// Execute the query and fetch results
		const queryResult = [];
		stmt.bind(bindings);

		while (stmt.step()) {
			const row = stmt.getAsObject();
			queryResult.push(stmt.getAsObject());
		}
		const records = queryResult;
		// Process results
		for (const row of records) {
			const ids = row.ids.split(',');
			const uniqueHits = [...new Set(ids)];

			const resultset = {
				address: row.ad,
				hits: uniqueHits,
				entries: []
			};

			const names = row.names.split(',');
			const chairmans = row.chairmans.split(',');
			const agents = row.agents?.split(',') || [];

			const hitsName = [];
			const hitsPeople = [];
			for (let i = 0; i < names.length; i++) {
				if (!hitsName.includes(names[i])) {
					hitsName.push(names[i]);
					hitsPeople[hitsName.length - 1] = [chairmans[i], agents[i]];

					resultset.entries.push({
						company: names[i],
						registration: ids[i],
						chairmen: chairmans[i] ? [chairmans[i]] : [],
						agents: agents[i] && agents[i] !== chairmans[i] ? [agents[i]] : []
					});
				} else {
					const index = hitsName.indexOf(names[i]);
					resultset.entries[index].registration += `,${ids[i]}`;
					if (chairmans[i] && !hitsPeople[index].includes(chairmans[i])) {
						hitsPeople[index].push(chairmans[i]);
						resultset.entries[index].chairmen.push(chairmans[i]);
					}
					if (agents[i] && !hitsPeople[index].includes(agents[i]) && agents[i] !== chairmans[i]) {
						resultset.entries[index].agents.push(agents[i]);
					}
				}
			}

			results.push(resultset);
		}
	}

	return { addresses: results };
}

/** Company Search **/
export function companySearch(_company, db) {
	const results = {
		companies: [],
		amendments: []
	};

	// Retrieve the "company" parameter from the query, if available
	let company = _company ? _company.trim().toLowerCase() : '';
	let check = '';
	let checkResolution = '';

	// If the search term is longer than 1 character, process it
	if (company.length > 1) {
		const bits = company.split(' ');
		let tmp = [];
		let acro = false;
		let acroBits = [];

		// Loop through each part of the search term
		bits.forEach((b) => {
			b = b.trim();

			// Handle acronyms in various forms (e.g., "C.R.C.K", "CRCK", "C R C K")
			if (b.length === 1) {
				acro = true;
				acroBits.push(b);
			} else {
				if (acro) {
					acro = false;
					check += ` (name LIKE '%${acroBits.join(' ')}%' OR name LIKE '%${acroBits.join('.')}%' OR name LIKE '%${acroBits.join('')}%') AND `;
					checkResolution += ` (resolution_text LIKE '%${acroBits.join(' ')}%' OR resolution_text LIKE '%${acroBits.join('.')}%' OR resolution_text LIKE '%${acroBits.join('')}%') AND `;
					tmp = [...tmp, acroBits.join(' '), acroBits.join('.'), acroBits.join('')];
					acroBits = [];
				}

				if (b.includes('.')) {
					const bSpace = b.replace(/\./g, ' ');
					const bNoDots = b.replace(/\./g, '');
					check += ` (name LIKE '%${b}%' OR name LIKE '%${bSpace}%' OR name LIKE '%${bNoDots}%') AND `;
					checkResolution += ` (resolution_text LIKE '%${b}%' OR resolution_text LIKE '%${bSpace}%' OR resolution_text LIKE '%${bNoDots}%') AND `;
					tmp = [...tmp, b, bSpace, bNoDots];
				} else {
					check += ` name LIKE '%${b}%' AND `;
					checkResolution += ` resolution_text LIKE '%${b}%' AND `;
					tmp.push(b);
				}
			}
		});

		// Handle any leftover acronyms
		if (acro) {
			check += ` (name LIKE '%${acroBits.join(' ')}%' OR name LIKE '%${acroBits.join('.')}%' OR name LIKE '%${acroBits.join('')}%') AND `;
			checkResolution += ` (resolution_text LIKE '%${acroBits.join(' ')}%' OR resolution_text LIKE '%${acroBits.join('.')}%' OR resolution_text LIKE '%${acroBits.join('')}%') AND `;
			tmp = [...tmp, acroBits.join(' '), acroBits.join('.'), acroBits.join('')];
		}

		company = tmp;
	}

	// Only proceed if there's a search term
	if (db && check) {
		const query = `
      SELECT group_concat(id, ',') as ids, name, 
             group_concat(chairman, ',') as chairmans, 
             group_concat(agent, ',') as agents 
      FROM (
        SELECT concat('R', id) as id, name, chairman, agent, '0000-01-01' as date 
        FROM companies WHERE ${check} 1=1 
        UNION 
        SELECT concat('A', id) as id, name, chairman, '' as agent, COALESCE(resolution_date, '1000-01-01') as date 
        FROM amendments WHERE ${check} 1=1 
        ORDER BY date
      ) as merged 
      GROUP BY name 
      ORDER BY name`;

		const stmt = db.prepare(query);
		const companiesResult = [];
		while (stmt.step()) {
			companiesResult.push(stmt.getAsObject());
		}
		const idCollection = [];

		// Process each company result
		companiesResult.forEach((row) => {
			const company = {
				name: row.name,
				ids: row.ids.split(','),
				chairmen: [],
				agents: []
			};
			idCollection.push(...company.ids);

			const chairmen = row.chairmans.split(',');
			const agents = row.agents?.split(',') || [];
			const hits = [];

			chairmen.forEach((chairman, i) => {
				if (!hits.includes(chairman)) {
					hits.push(chairman);
					company.chairmen.push(chairman);
				}

				if (agents[i] && !hits.includes(agents[i])) {
					hits.push(agents[i]);
					company.agents.push(agents[i]);
				}
			});

			results.companies.push(company);
		});

		// Amendments/Resolutions
		const amendmentQuery = `
      SELECT concat('A', id) as id, name, resolution_date, resolution_text, chairman, chairman_gender 
      FROM amendments 
      WHERE (${checkResolution.slice(0, -4)}) OR resolution_text LIKE '%${company.join(' ')}%' 
      ORDER BY name, resolution_date`;

		const stmt2 = db.prepare(amendmentQuery);
		const amendmentsResult = [];
		while (stmt2.step()) {
			amendmentsResult.push(stmt2.getAsObject());
		}

		// Process each amendment result
		amendmentsResult.forEach((row) => {
			let resolutionText = row.resolution_text
				.replace(/-\s*res/g, '<br>- Res')
				.replace(/\+\s*res/g, '<br>- Res')
				.replace(/([0-9]+)\.\s*res/g, '<br>- Res');

			// Highlight search term matches in resolution text
			const searchHits = company;
			searchHits.forEach((hit) => {
				const highlighted = `<span class="highlight">${hit}</span>`;
				resolutionText = resolutionText.replace(new RegExp(hit, 'gi'), highlighted);
			});

			const amendment = {
				id: row.id,
				name: row.name,
				resolution_date: row.resolution_date,
				resolution_text: resolutionText
			};

			if (row.chairman) {
				amendment.chairman = {
					gender: row.chairman_gender,
					name: row.chairman
				};
			}

			results.amendments.push(amendment);
		});
	}

	return results;
}
