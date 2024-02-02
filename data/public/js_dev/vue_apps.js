const appProps = {
  employee: {
    mode: 'view',
    employee_id: 1,
  },
  orders: {
    mode: 'add',
    // contract_id: 31
    contractor_id: 1581,
  },
  service: {
    mode: 'edit',
    contact_id: 39,
    contact_node_id: 1579
  },
  staff: {
    mode: 'view',
    contact_node_id: 1579,
    id: 29,
    contact_id: 29,
  },
  contract: {
    mode: 'add',
    contractor_id: 1581,
  },
}


jQuery( document ).ready(function() {

  const appsContainer = jQuery("#vueAppContainer");
  const appSelector = jQuery("#vueAppSelector");
  const selectorsBlock = jQuery('#vueAppSelectors');
  const vueBuilder = VueBuilder;

  setTimeout(()=>{
    const apps = vueBuilder.getApps();

    let currentApp = null;

    if (apps) {
        apps.forEach((app) => {
          appsContainer.append(`<div id="${app.name}" data-name="${app.name}"></div>`);
          appSelector.append(`<option value="${app.name}">${app.name}</option>`);
        });

        appSelector.on("change", function () {
            const appName = this.value
            const appContainer = jQuery(`#${appName}`);
            if (currentApp) currentApp.destroy();
            if (appName !== "-1") {
                const app = apps.find(a => a.name === appName)

                if (appProps[appName]) {
                  Object.keys(appProps[appName]).forEach(prop => {

                    appContainer.attr(`data-${prop}`, appProps[appName][prop]);
                  })
                }

                currentApp = vueBuilder.init(appName)

                if (currentApp && app && app.events) {
                    app.events.forEach(e => {
                      currentApp.on(e.name, (payload) => {
                        console.log(`triggered event ${e.name} on ${app.name} with payload`, payload);
                      })
                    })
                }
            }
        });
    }
  }, 50);
});

