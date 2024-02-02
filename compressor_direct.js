//echo $NODE_PATH
//export NODE_PATH=export NODE_PATH=/usr/lib/node_modules/
//forever start  --minUptime 1000 --spinSleepTime 1000 -o Logs/daedalus.log -e Logs/daedalus_errors.log -c "node --expose-gc" daedalus.js
//node --expose-gc daedalus.js


const UglifyJS = require("uglify-js");
const fs       = require("fs");
const { Pool, Client } = require("pg");

const getDBPool = function() {
  return new Pool({
    user: "fullrest_daedalus",
    database: "daedalus_project",
    password: "sdk666Annoy999#$*dd*&",
    max: 4
  });
};

const dirPublic = 'public/';

const jsFileTranslate = (fileContent, resourceDir, resourceName, langName) => {
  const langResDir = resourceDir.replace('js/', 'js_lang/');

  const langFileName = resourceName.replace('.js', '.' + langName + '.json');

  if (fs.existsSync(dirPublic + langResDir + langFileName)) {
    const langContent = JSON.parse(fs.readFileSync(dirPublic + langResDir + langFileName, 'utf8'));

    Object.keys(langContent).forEach(langKey => {
      fileContent = fileContent.replace(new RegExp("'{" + langKey + "}'", 'gm'), "'" + langContent[langKey] + "'");
    });
  }

  let compressedJs = UglifyJS.minify(fileContent).code;

  if (!compressedJs) {
    console.log('cant compress: ' + resourceDir + resourceName);

    compressedJs = '';
  }

  return compressedJs;
};

const jsFileWrite = (fileContent, dirMin, resourceDir, resourceName, langName, currentVersion) => {
  console.log(dirMin, resourceDir, resourceName, langName, currentVersion);

  const dirWritePath = dirMin + resourceDir.replace('js/', '').replace(/\//gmiu, '_');
  const dirWriteName = fileVer => resourceName.replace('.js', '_' + fileVer + '_' + langName + '.js');

  fs.unlink(dirWritePath + dirWriteName(currentVersion - 1), err => {console.log(err)});
  console.log('unlinked');
  fs.writeFileSync(dirWritePath + dirWriteName(currentVersion), /*UglifyJS.minify(*/fileContent/*).code*/, { encoding: 'utf8' });
  console.log('writen');

  fileContent = null;
};


const compressJs = async (siteId) => {
  const pool = getDBPool();

  console.log(siteId);

  let siteRow = await pool.query("SELECT * FROM sites WHERE site_id = $1", [siteId]);

  console.log(siteRow);

  siteRow = siteRow.rows[0];
  const minVersion = siteRow.site_min_ver_js;

  if (!siteRow) {
    throw new Error("no such site id = "+siteId);
  }

  const dirMin = dirPublic + 'min/' + siteRow.site_name + '/';
  const langList = ['ru', 'en'];

  // Минификации всех приложений

  console.log('step 1');
  const requireJSQuery = pool.query(`
      SELECT
          'js/app/' AS resource_dir,
          (a.app_name || '.js') AS resource_name
      FROM
           apps AS a
      
      UNION
      
      SELECT
          ('js/site/' || s.site_name || '/')  AS resource_dir,
          (sc.controller_name || '.js') AS resource_name
      FROM
          sites AS s,
          sites_controllers AS sc,
          sites_controllers_to_sites AS scts
      WHERE
          s.site_id = $1 AND
          scts.site_id = s.site_id AND
          sc.controller_id = scts.controller_id
      
      UNION
      
      SELECT
          r.resource_dir,
          r.resource_name
      FROM
          resources AS r
      WHERE
          r.type_id = 2 AND
          r.package_id IS NULL
    `,
    [siteId]
  );

  console.log('step 2');
  const packagesQuery = pool.query('SELECT * FROM resources_packages WHERE type_id = 2 AND package_compress_group = 1');
  const siteResourcesQuery = pool.query("SELECT * FROM dp_resources_get_list_js_compiled($1::integer)", [siteId]);

  const requireJSList = await requireJSQuery;


  console.log('step 3');
  const requirePromises = [];

  requireJSList.rows.forEach(({resource_dir, resource_name}) => requirePromises.push(new Promise(resolve => {
    console.log(dirPublic + resource_dir + resource_name);
    let jsContent = fs.readFileSync(dirPublic + resource_dir + resource_name, 'utf8');

    langList.forEach(langName => {
      let localJsContent = jsFileTranslate(jsContent, resource_dir, resource_name, langName);

      jsFileWrite(localJsContent, dirMin, resource_dir, resource_name, langName, minVersion);

      localJsContent = null;
    });

    jsContent = null;

    resolve();
  })));

  console.log('step 4');
  const packagesList = await packagesQuery;

  const packagesPromises = [];

  packagesList.rows.forEach(packageRow => packagesPromises.push(new Promise(async resolve => {
    const resourcesList = await pool.query(
      'SELECT resource_dir, resource_name FROM resources WHERE package_id = $1 ORDER BY resource_order ASC',
      [packageRow.package_id]
    );

    const fileContents = {
      ru: '',
      en: ''
    };

    console.log(resourcesList)
    console.log('step 5');
    resourcesList.rows.forEach(({resource_dir, resource_name}) => {
      let jsContent = fs.readFileSync(dirPublic + resource_dir + resource_name, 'utf8');

      langList.forEach(langName => {
        let localJsContent = jsFileTranslate(jsContent, resource_dir, resource_name, langName);

        fileContents[langName] += ` 
        ; 
        ` + localJsContent;
        localJsContent = null;
      });

      jsContent = null;
    });

    console.log('step 6');
    jsFileWrite(fileContents.ru, dirMin, 'js/', packageRow.package_name + '.js', 'ru', minVersion);
    jsFileWrite(fileContents.en, dirMin, 'js/', packageRow.package_name + '.js', 'en', minVersion);

    console.log('step 7');
    resolve();
  })));


  const sireResourcesList = await siteResourcesQuery;

  const siteResourceContents = {
    ru: '',
    en: ''
  };

  sireResourcesList.rows.forEach(({resource_dir, resource_name}) => {
    let jsContent = fs.readFileSync(dirPublic + resource_dir + resource_name, 'utf8');

    langList.forEach(langName => {
      let localJsContent = jsFileTranslate(jsContent, resource_dir, resource_name, langName);

      siteResourceContents[langName] += ` 
        ; 
        ` + localJsContent;

      localJsContent = null;
    });

    jsContent = null;
  });

  await Promise.all(packagesPromises);
  await Promise.all(requirePromises);

  jsFileWrite(siteResourceContents.ru, dirMin, 'js/', siteRow.site_name + '.js', 'ru', minVersion);
  jsFileWrite(siteResourceContents.en, dirMin, 'js/', siteRow.site_name + '.js', 'en', minVersion);



  pool.end();
};


console.log('pam pam');
compressJs(4).then(() => {
  console.log('ready');
});



