import initSqlJs from 'sql.js';

const sqlStatements = await fetch('/db-sql.txt').then((r) => r.text());

const wasmUrl = '/sql-wasm.wasm';

export const create = () =>
	initSqlJs({
		// Required to load the wasm binary asynchronously. Of course, you can host it wherever you want
		// You can omit locateFile completely when running in node
		locateFile: () => wasmUrl
	})
		.then((SQL) => {
			return new SQL.Database();
		})
		.then((db) => {
			// we have to import them one at a time... so i added breaks
			const sts = sqlStatements.split('-- break');

			console.time('adding statements to initial database');
			for (const st of sts) {
				db.exec(st);
			}
			console.timeEnd('adding statements to initial database');
			return db;
		});

export const eventual = create();
