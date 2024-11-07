## Developing

Install and use the correct node version using

`nvm use`

Install the dependencies using

`npm install`

```bash
npm run dev
# or start the server and open the app in a new browser tab
npm run dev -- --open
```

## Building

To create a production version of your app:

```bash
npm run build
```

You can preview the production build with `npm run preview`.

> To deploy your app, you may need to install an [adapter](https://kit.svelte.dev/docs/adapters) for your target environment.

Depending on which adapter you use in `svelte.config.js`

```
//import adapter from '@sveltejs/adapter-node';
import adapter from '@sveltejs/adapter-static';
```

You will get a static site in `./build` or a runing server.
