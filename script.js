// script.js - Lógica de la Interfaz de Usuario
// Este archivo controla todo lo que sucede en la pantalla del navegador.
// Escucha lo que hace el usuario (clics, escritura) y actualiza la página.

document.addEventListener("DOMContentLoaded", () => {
  // --- Referencias a Elementos de la Pantalla ---
  // Guardamos enlaces a los botones, tablas y cuadros de texto para usarlos fácilmente.
  const dom = {
    inputs: {
      tiempo: document.getElementById("tiempo_turno"), // Donde se escribe el tiempo disponible
      demanda: document.getElementById("demanda"), // Donde se escribe la demanda
      reglas: document.getElementsByName("regla"), // Las opciones de reglas de prioridad
    },
    errors: {
      tiempo: document.getElementById("err-tiempo"),
      demanda: document.getElementById("err-demanda"),
      table: document.getElementById("table-errors"),
    },
    tableBody: document.querySelector("#tasks-table tbody"), // El cuerpo de la tabla de tareas
    buttons: {
      add: document.getElementById("btn-add-row"),
      clear: document.getElementById("btn-clear-rows"),
      calculate: document.getElementById("btn-calculate"), // El botón gigante "CALCULAR"
      newScenario: document.getElementById("btn-new-scenario"),
      loadExample: document.getElementById("btn-load-example"),
      random: document.getElementById("btn-random"),
      help: document.getElementById("btn-help"),
      clearResults: document.getElementById("btn-clear-results"),
      toggleSteps: document.getElementById("btn-toggle-steps"),
    },
    results: {
      container: document.getElementById("results-container"), // Donde aparecen los resultados
      loading: document.getElementById("loading-indicator"),
      metrics: {
        takt: document.getElementById("res-takt"),
        sum: document.getElementById("res-sum"),
        nt: document.getElementById("res-nt"),
        nreal: document.getElementById("res-nreal"),
        eff: document.getElementById("res-efficiency"),
      },
      grid: document.getElementById("stations-grid"), // La visualización de las estaciones (cajitas)
      stepsContent: document.getElementById("steps-content"),
    },
    modal: {
      el: document.getElementById("helpModal"),
      // Bootstrap modal instance will be created in init
    },
  };

  let helpModalInstance = null;

  // --- Configuración Inicial ---
  const STORAGE_KEY = "balanceo_spa_state"; // Clave para guardar datos en el navegador

  // Datos de ejemplo para el botón "Cargar Ejemplo"
  const EXAMPLE_DATA = {
    tiempo: 480,
    demanda: 360,
    tareas: [
      { id: "A", dur: 12, prec: "" },
      { id: "B", dur: 60, prec: "" },
      { id: "C", dur: 15, prec: "A" },
      { id: "D", dur: 50, prec: "A" },
      { id: "E", dur: 10, prec: "B" },
      { id: "F", dur: 55, prec: "B" },
      { id: "G", dur: 18, prec: "C" },
      { id: "H", dur: 22, prec: "D" },
      { id: "I", dur: 14, prec: "E,F" },
      { id: "J", dur: 30, prec: "G" },
      { id: "K", dur: 40, prec: "H" },
      { id: "L", dur: 20, prec: "I,J,K" },
    ],
  };

  // Arrancamos la aplicación
  init();

  function init() {
    loadState(); // Recuperar datos guardados anteriormente si existen
    setupEventListeners(); // Activar los botones
  }

  // Configura qué pasa cuando el usuario hace clic o escribe
  function setupEventListeners() {
    // Botones Principales
    if (dom.buttons.add)
      dom.buttons.add.addEventListener("click", () => addRow()); // Añadir nueva fila
    if (dom.buttons.clear)
      dom.buttons.clear.addEventListener("click", confirmClearTable); // Borrar todo
    if (dom.buttons.calculate)
      dom.buttons.calculate.addEventListener("click", handleCalculate); // ¡Calcular!
    if (dom.buttons.newScenario)
      dom.buttons.newScenario.addEventListener("click", confirmNewScenario);
    if (dom.buttons.loadExample)
      dom.buttons.loadExample.addEventListener("click", loadExample);
    if (dom.buttons.random)
      dom.buttons.random.addEventListener("click", generateRandomScenario);
    if (dom.buttons.clearResults)
      dom.buttons.clearResults.addEventListener("click", clearResults);

    // Ayuda (Modal)
    if (dom.buttons.help) {
      dom.buttons.help.addEventListener("click", () => {
        if (helpModalInstance) helpModalInstance.show();
      });
    }

    // Initialize Bootstrap Modal
    if (dom.modal.el) {
      helpModalInstance = new bootstrap.Modal(dom.modal.el);
    }

    // Mostrar/Ocultar detalles paso a paso
    if (dom.buttons.toggleSteps) {
      dom.buttons.toggleSteps.addEventListener("click", () => {
        dom.results.stepsContent.classList.toggle("d-none");
      });
    }

    // Guardar automáticamente cuando el usuario escribe
    if (dom.inputs.tiempo)
      dom.inputs.tiempo.addEventListener("input", saveState);
    if (dom.inputs.demanda)
      dom.inputs.demanda.addEventListener("input", saveState);
  }

  // --- Gestión de la Tabla de Tareas ---

  // Genera un ID automático (A, B, C...)
  function getNextId() {
    const rows = dom.tableBody.querySelectorAll("tr");
    if (rows.length === 0) return "A";

    const usedIds = Array.from(rows).map(
      (r) => r.querySelector(".task-id").value
    );
    let charCode = 65; // Código ASCII para 'A'
    while (usedIds.includes(String.fromCharCode(charCode))) {
      charCode++;
      if (charCode > 90) break; // Límite Z
    }
    return String.fromCharCode(charCode);
  }

  // Agrega una fila a la tabla HTML
  function addRow(data = null) {
    const tr = document.createElement("tr");
    const id = data ? data.id : getNextId();
    const dur = data ? data.dur : 10;
    const prec = data ? data.prec : "";

    tr.innerHTML = `
            <td><input type="text" class="form-control form-control-sm task-id" value="${id}" placeholder="ID"></td>
            <td><input type="number" class="form-control form-control-sm task-dur" value="${dur}" min="1"></td>
            <td><input type="text" class="form-control form-control-sm task-prec" value="${prec}" placeholder="Ej: A,C"></td>
            <td>
                <button class="btn btn-sm btn-outline-secondary btn-dup" title="Duplicar"><i class="bi bi-files"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-delete" title="Eliminar"><i class="bi bi-trash"></i></button>
            </td>
        `;

    // Botón eliminar fila
    tr.querySelector(".btn-delete").addEventListener("click", () => {
      tr.remove();
      saveState();
    });

    // Botón duplicar fila
    tr.querySelector(".btn-dup").addEventListener("click", () => {
      const currentData = getRowData(tr);
      addRow({ ...currentData, id: "" }); // Duplicar sin ID para obligar a generar uno nuevo
      saveState();
    });

    // Validar mientras se escribe
    tr.querySelectorAll("input").forEach((input) => {
      input.addEventListener("input", () => {
        saveState();
        validateRow(tr);
      });
      input.addEventListener("blur", () => validateRow(tr));
    });

    dom.tableBody.appendChild(tr);
    saveState();
  }

  // Lee los datos de una fila HTML
  function getRowData(tr) {
    return {
      id: tr.querySelector(".task-id").value.trim().toUpperCase(),
      dur: parseInt(tr.querySelector(".task-dur").value) || 0,
      prec: tr.querySelector(".task-prec").value.toUpperCase(),
    };
  }

  function confirmClearTable() {
    // if (confirm("¿Borrar todas las tareas?")) {
    dom.tableBody.innerHTML = "";
    saveState();
    // }
  }

  function confirmNewScenario() {
    // if (confirm("¿Iniciar nuevo escenario? Se borrarán todos los datos.")) {
    dom.inputs.tiempo.value = "";
    dom.inputs.demanda.value = "";
    dom.tableBody.innerHTML = "";
    clearResults();
    addRow({ id: "A", dur: 10, prec: "" });
    saveState();
    // }
  }

  function loadExample() {
    // if (
    //   dom.tableBody.children.length > 0 &&
    //   !confirm("¿Sobrescribir datos actuales con el ejemplo?")
    // )
    //   return;

    dom.inputs.tiempo.value = EXAMPLE_DATA.tiempo;
    dom.inputs.demanda.value = EXAMPLE_DATA.demanda;
    dom.tableBody.innerHTML = "";
    EXAMPLE_DATA.tareas.forEach((t) => addRow(t));
    clearResults();
    saveState();
  }

  // --- Generador de Escenarios Aleatorios ---
  // Crea un problema de balanceo al azar para probar la herramienta
  function generateRandomScenario() {
    // if (
    //   dom.tableBody.children.length > 0 &&
    //   !confirm("¿Sobrescribir con datos aleatorios?")
    // )
    //   return;

    const numTasks = Math.floor(Math.random() * 6) + 8; // Entre 8 y 13 tareas
    const tasks = [];
    const letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    for (let i = 0; i < numTasks; i++) {
      const id = letters[i];
      const dur = Math.floor(Math.random() * 50) + 10; // Duración entre 10s y 60s
      let prec = "";

      // A veces agregamos dependencias (solo de tareas anteriores para evitar ciclos simples)
      if (i > 0 && Math.random() > 0.3) {
        const numPrecs = Math.floor(Math.random() * 2) + 1;
        const possiblePrecs = tasks.slice(0, i).map((t) => t.id);

        if (possiblePrecs.length > 0) {
          const selected = possiblePrecs
            .sort(() => 0.5 - Math.random())
            .slice(0, numPrecs);
          prec = selected.join(",");
        }
      }

      tasks.push({ id, dur, prec });
    }

    dom.inputs.tiempo.value = 480;
    dom.inputs.demanda.value = Math.floor(Math.random() * 200) + 200; // Demanda aleatoria
    dom.tableBody.innerHTML = "";
    tasks.forEach((t) => addRow(t));
    clearResults();
    saveState();
  }

  // --- Validación de Datos ---
  // Verifica que todo esté correcto antes de enviar al servidor
  function validate() {
    let isValid = true;
    const errors = [];

    // Validar configuración global
    if (dom.inputs.tiempo.value <= 0) {
      dom.errors.tiempo.classList.remove("d-none");
      dom.inputs.tiempo.classList.add("is-invalid");
      isValid = false;
    } else {
      dom.errors.tiempo.classList.add("d-none");
      dom.inputs.tiempo.classList.remove("is-invalid");
    }

    if (dom.inputs.demanda.value <= 0) {
      dom.errors.demanda.classList.remove("d-none");
      dom.inputs.demanda.classList.add("is-invalid");
      isValid = false;
    } else {
      dom.errors.demanda.classList.add("d-none");
      dom.inputs.demanda.classList.remove("is-invalid");
    }

    // Validar filas de la tabla
    const rows = dom.tableBody.querySelectorAll("tr");
    const ids = new Set();
    const rowData = [];

    rows.forEach((row, index) => {
      const inputs = row.querySelectorAll("input");
      const idInput = inputs[0];
      const durInput = inputs[1];

      const id = idInput.value.trim().toUpperCase();
      const dur = parseInt(durInput.value);

      inputs.forEach((i) => i.classList.remove("is-invalid"));

      // ID obligatorio y único
      if (!id) {
        errors.push(`Fila ${index + 1}: ID vacío.`);
        idInput.classList.add("is-invalid");
        isValid = false;
      } else if (ids.has(id)) {
        errors.push(`Fila ${index + 1}: ID "${id}" duplicado.`);
        idInput.classList.add("is-invalid");
        isValid = false;
      }
      ids.add(id);

      // Duración positiva
      if (dur <= 0 || isNaN(dur)) {
        errors.push(`Fila ${index + 1}: Duración inválida.`);
        durInput.classList.add("is-invalid");
        isValid = false;
      }

      rowData.push({ row, id, precStr: inputs[2].value.toUpperCase() });
    });

    // Validar precedencias (que existan y no sean la misma tarea)
    rowData.forEach((item, index) => {
      if (item.precStr) {
        const precs = item.precStr
          .split(",")
          .map((p) => p.trim())
          .filter((p) => p !== "");
        precs.forEach((p) => {
          if (!ids.has(p)) {
            errors.push(
              `Fila ${index + 1} (Tarea ${
                item.id
              }): Precedencia "${p}" no existe.`
            );
            item.row.querySelectorAll("input")[2].classList.add("is-invalid");
            isValid = false;
          }
          if (p === item.id) {
            errors.push(
              `Fila ${index + 1}: Tarea "${
                item.id
              }" no puede depender de sí misma.`
            );
            isValid = false;
          }
        });
      }
    });

    // Mostrar errores si los hay
    if (errors.length > 0) {
      dom.errors.table.innerHTML = errors.join("<br>");
      dom.errors.table.classList.remove("d-none");
    } else {
      dom.errors.table.classList.add("d-none");
    }

    return isValid;
  }

  // Validación visual rápida de una fila individual
  function validateRow(tr) {
    const idInput = tr.querySelector(".task-id");
    const durInput = tr.querySelector(".task-dur");
    const precInput = tr.querySelector(".task-prec");

    // Reset styles
    idInput.classList.remove("is-invalid");
    durInput.classList.remove("is-invalid");
    precInput.classList.remove("is-invalid");

    const id = idInput.value.trim().toUpperCase();
    const dur = parseInt(durInput.value);
    const precStr = precInput.value.toUpperCase();

    // 1. Advertencia de ID vacío
    if (!id) {
      idInput.classList.add("input-warning");
    } else {
      idInput.classList.remove("input-warning");
    }

    // 2. Tiempo negativo
    if (dur < 0) {
      durInput.value = 0;
      durInput.classList.add("is-invalid");
    }

    // 3. Existencia de Precedencias
    if (precStr) {
      const allIds = Array.from(dom.tableBody.querySelectorAll(".task-id")).map(
        (i) => i.value.trim().toUpperCase()
      );
      const precs = precStr
        .split(",")
        .map((p) => p.trim())
        .filter((p) => p !== "");

      let precError = false;
      precs.forEach((p) => {
        if (!allIds.includes(p) && p !== "") {
          precError = true;
        }
      });

      if (precError) {
        precInput.classList.add("is-invalid");
      }
    }
  }

  // --- Comunicación con el Servidor (Cálculo) ---
  async function handleCalculate() {
    if (!validate()) return; // Si hay errores, no enviamos nada

    const tiempoTurno = dom.inputs.tiempo.value;
    const demanda = dom.inputs.demanda.value;

    // Obtener regla seleccionada
    let regla = "DEFAULT";
    for (const r of dom.inputs.reglas) {
      if (r.checked) {
        regla = r.value;
        break;
      }
    }

    // Preparar datos para enviar
    const rows = dom.tableBody.querySelectorAll("tr");
    const tareas = [];
    rows.forEach((row) => {
      const d = getRowData(row);
      tareas.push({
        letra_tarea: d.id,
        duracion: d.dur,
        precedencias: d.prec,
      });
    });

    try {
      setLoading(true); // Mostrar "Cargando..."

      // Enviar datos a PHP (api.php)
      const response = await fetch("api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          tiempo_turno: tiempoTurno,
          demanda: demanda,
          tareas: tareas,
          regla: regla,
        }),
      });

      const result = await response.json();

      if (response.ok && result.success) {
        renderResults(result.data); // Mostrar resultados si todo salió bien
      } else {
        throw new Error(result.message || "Error en el servidor");
      }
    } catch (error) {
      alert("Error: " + error.message);
    } finally {
      setLoading(false); // Ocultar "Cargando..."
    }
  }

  function setLoading(isLoading) {
    if (isLoading) {
      dom.buttons.calculate.disabled = true;
      dom.results.loading.classList.remove("d-none");
    } else {
      dom.buttons.calculate.disabled = false;
      dom.results.loading.classList.add("d-none");
    }
  }

  // --- Mostrar Resultados en Pantalla ---
  function renderResults(data) {
    dom.results.container.classList.remove("d-none");

    // Mostrar métricas principales
    dom.results.metrics.takt.textContent =
      parseFloat(data.takt_time).toFixed(2) + " s";
    dom.results.metrics.sum.textContent = data.suma_tiempos + " s";
    dom.results.metrics.nt.textContent = data.num_teorico_estaciones;
    dom.results.metrics.nreal.textContent = data.num_estaciones;
    dom.results.metrics.eff.textContent = data.eficiencia + "%";

    // Dibujar las "tarjetas" de las estaciones
    dom.results.grid.innerHTML = "";
    data.estaciones.forEach((est) => {
      const card = document.createElement("div");
      card.className = "station-card";

      let tareasHtml = "";
      est.tareas.forEach((t) => {
        tareasHtml += `<span class="task-badge">${t.letra} (${t.duracion}s)</span>`;
      });

      // Alerta visual si hay mucho tiempo ocioso (más del 20% del Takt Time)
      const isHighIdle = est.tiempo_ocioso > data.takt_time * 0.2;
      const ociosoClass = isHighIdle ? "text-danger" : "text-success";
      const ociosoIcon = isHighIdle ? "⚠️" : "";

      card.innerHTML = `
                <div class="station-header">
                    <span>Estación ${est.id}</span>
                    <span>${est.tiempo_total}s / ${parseFloat(
        data.takt_time
      ).toFixed(1)}s</span>
                </div>
                <div class="station-tasks">
                    ${tareasHtml}
                </div>
                <div class="station-footer">
                    <div class="stat-row">
                        <span>Ocioso:</span>
                        <span class="${ociosoClass}">${ociosoIcon} ${parseFloat(
        est.tiempo_ocioso
      ).toFixed(1)}s</span>
                    </div>
                </div>
            `;
      dom.results.grid.appendChild(card);
    });

    renderDetailedTable(data);
    renderGraphSVG(data);
    renderStepByStep(data.paso_a_paso);

    // Desplazar la pantalla hacia los resultados
    dom.results.container.scrollIntoView({ behavior: "smooth" });
  }

  // Muestra la tabla detallada de decisiones
  function renderDetailedTable(data) {
    const tbody = document.querySelector("#detailed-table tbody");
    if (!tbody) return;
    tbody.innerHTML = "";

    data.paso_a_paso.forEach((estacion) => {
      estacion.pasos.forEach((paso, index) => {
        const tr = document.createElement("tr");

        // Agrupar celdas de estación
        let stationCell = "";
        if (index === 0) {
          stationCell = `<td rowspan="${estacion.pasos.length}" class="station-cell">${estacion.estacion_id}</td>`;
        }

        const selectedTask = paso.candidatos.find(
          (c) => c.id === paso.seleccionada
        );
        const duration = selectedTask ? selectedTask.duracion : "-";
        const remaining = (paso.tiempo_restante - duration).toFixed(1);

        const factibles = paso.candidatos.map((c) => c.id).join(", ");

        // Mostrar qué tareas tenían más sucesores o duración (para entender la decisión)
        const maxSuccVal = Math.max(...paso.candidatos.map((c) => c.sucesores));
        const maxSuccTasks = paso.candidatos
          .filter((c) => c.sucesores === maxSuccVal)
          .map((c) => c.id)
          .join(", ");

        const maxDurVal = Math.max(...paso.candidatos.map((c) => c.duracion));
        const maxDurTasks = paso.candidatos
          .filter((c) => c.duracion === maxDurVal)
          .map((c) => c.id)
          .join(", ");

        tr.innerHTML = `
                    ${stationCell}
                    <td><strong>${paso.seleccionada}</strong></td>
                    <td>${duration}</td>
                    <td>${remaining}</td>
                    <td>${factibles}</td>
                    <td>${maxSuccTasks} (${maxSuccVal})</td>
                    <td>${maxDurTasks} (${maxDurVal})</td>
                    <td><strong>${paso.seleccionada}</strong></td>
                `;
        tbody.appendChild(tr);
      });
    });
  }

  // Dibuja el grafo de conexiones (SVG)
  function renderGraphSVG(data) {
    const container = document.getElementById("graph-container");
    if (!container) return;
    container.innerHTML = "";

    // Preparar nodos
    const estaciones = data.estaciones;
    const nodes = {};

    estaciones.forEach((est, i) => {
      est.tareas.forEach((t, j) => {
        nodes[t.letra] = {
          id: t.letra,
          dur: t.duracion,
          station: est.id,
          x: i * 150 + 80, // Posición X basada en la estación
          y: j * 80 + 50, // Posición Y basada en el orden dentro de la estación
        };
      });
    });

    // Preparar enlaces (flechas)
    const links = [];
    const rows = dom.tableBody.querySelectorAll("tr");
    rows.forEach((row) => {
      const d = getRowData(row);
      if (d.prec) {
        d.prec.split(",").forEach((p) => {
          p = p.trim();
          if (nodes[d.id] && nodes[p]) {
            links.push({ source: nodes[p], target: nodes[d.id] });
          }
        });
      }
    });

    // Configurar SVG
    const width = estaciones.length * 160 + 50;
    const height = 600;
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("width", width);
    svg.setAttribute("height", height);
    svg.setAttribute("viewBox", `0 0 ${width} ${height}`);
    svg.style.border = "1px solid #ccc";
    svg.style.background = "#f9f9f9";

    // Dibujar fondo de estaciones
    estaciones.forEach((est, i) => {
      const rect = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "rect"
      );
      rect.setAttribute("x", i * 150 + 10);
      rect.setAttribute("y", 10);
      rect.setAttribute("width", 140);
      rect.setAttribute("height", height - 20);
      rect.setAttribute("rx", 10);
      rect.setAttribute("fill", i % 2 === 0 ? "#e0f2fe" : "#f0f9ff");
      rect.setAttribute("stroke", "#bae6fd");
      svg.appendChild(rect);

      const label = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "text"
      );
      label.setAttribute("x", i * 150 + 80);
      label.setAttribute("y", 30);
      label.setAttribute("text-anchor", "middle");
      label.setAttribute("font-weight", "bold");
      label.setAttribute("fill", "#0284c7");
      label.textContent = `Estación ${est.id}`;
      svg.appendChild(label);
    });

    // Definir punta de flecha
    const defs = document.createElementNS("http://www.w3.org/2000/svg", "defs");
    defs.innerHTML = `
            <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="28" refY="3.5" orient="auto">
                <polygon points="0 0, 10 3.5, 0 7" fill="#64748b" />
            </marker>
        `;
    svg.appendChild(defs);

    // Dibujar líneas
    links.forEach((l) => {
      const line = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "line"
      );
      line.setAttribute("x1", l.source.x);
      line.setAttribute("y1", l.source.y);
      line.setAttribute("x2", l.target.x);
      line.setAttribute("y2", l.target.y);
      line.setAttribute("stroke", "#64748b");
      line.setAttribute("stroke-width", "2");
      line.setAttribute("marker-end", "url(#arrowhead)");
      svg.appendChild(line);
    });

    // Dibujar círculos (nodos)
    Object.values(nodes).forEach((n) => {
      const g = document.createElementNS("http://www.w3.org/2000/svg", "g");

      const circle = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "circle"
      );
      circle.setAttribute("cx", n.x);
      circle.setAttribute("cy", n.y);
      circle.setAttribute("r", 25);
      circle.setAttribute("fill", "#2563eb");
      circle.setAttribute("stroke", "white");
      circle.setAttribute("stroke-width", "2");
      g.appendChild(circle);

      const text = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "text"
      );
      text.setAttribute("x", n.x);
      text.setAttribute("y", n.y + 5);
      text.setAttribute("text-anchor", "middle");
      text.setAttribute("fill", "white");
      text.setAttribute("font-weight", "bold");
      text.textContent = n.id;
      g.appendChild(text);

      const durText = document.createElementNS(
        "http://www.w3.org/2000/svg",
        "text"
      );
      durText.setAttribute("x", n.x + 28);
      durText.setAttribute("y", n.y - 10);
      durText.setAttribute("font-size", "10");
      durText.setAttribute("fill", "#333");
      durText.textContent = n.dur;
      g.appendChild(durText);

      svg.appendChild(g);
    });

    container.appendChild(svg);
  }

  // Muestra el log detallado paso a paso (texto desplegable)
  function renderStepByStep(log) {
    const container = dom.results.stepsContent;
    container.innerHTML = "";

    if (!log || log.length === 0) {
      container.innerHTML = "<p>No hay detalles disponibles.</p>";
      return;
    }

    log.forEach((estacionLog) => {
      const stationDiv = document.createElement("div");
      stationDiv.className = "step-station";

      const header = document.createElement("div");
      header.className = "step-station-header";
      header.innerHTML = `<span>Estación ${estacionLog.estacion_id}</span> <span style="font-size:0.8em">▼</span>`;

      const details = document.createElement("div");
      details.className = "step-details d-none";

      header.addEventListener("click", () => {
        details.classList.toggle("d-none");
      });

      estacionLog.pasos.forEach((paso, idx) => {
        const iterDiv = document.createElement("div");
        iterDiv.className = "step-iteration";

        const candidatosStr = paso.candidatos
          .map((c) => `${c.id}(${c.sucesores} suc)`)
          .join(", ");

        iterDiv.innerHTML = `
                    <div style="display:flex; justify-content:space-between; font-weight:600;">
                        <span>Paso ${idx + 1}</span>
                        <span>Restante (Inicio): ${parseFloat(
                          paso.tiempo_restante
                        ).toFixed(1)}s</span>
                    </div>
                    <div style="font-size:0.9em; color:#555; margin-top:4px;">
                        Candidatos: [${candidatosStr}]
                    </div>
                    <div style="font-size:0.9em; margin-top:4px;">
                        Selección: <strong style="color:var(--primary-color)">${
                          paso.seleccionada
                        }</strong> 
                        <span style="color:#888; font-size:0.85em">(${
                          paso.criterio
                        })</span>
                    </div>
                `;
        details.appendChild(iterDiv);
      });

      stationDiv.appendChild(header);
      stationDiv.appendChild(details);
      container.appendChild(stationDiv);
    });
  }

  function clearResults() {
    dom.results.container.classList.add("d-none");
    dom.results.grid.innerHTML = "";
    dom.results.stepsContent.innerHTML = "";
    const graph = document.getElementById("graph-viz");
    if (graph) graph.remove();
  }

  // --- Persistencia (Guardar datos en el navegador) ---
  function saveState() {
    const state = {
      tiempo: dom.inputs.tiempo.value,
      demanda: dom.inputs.demanda.value,
      tareas: [],
    };

    dom.tableBody.querySelectorAll("tr").forEach((row) => {
      state.tareas.push(getRowData(row));
    });

    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
  }

  function loadState() {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
      try {
        const state = JSON.parse(saved);
        if (state.tiempo) dom.inputs.tiempo.value = state.tiempo;
        if (state.demanda) dom.inputs.demanda.value = state.demanda;
        if (state.tareas && Array.isArray(state.tareas)) {
          dom.tableBody.innerHTML = "";
          state.tareas.forEach((t) => addRow(t));
        }
      } catch (e) {
        console.error("Error cargando estado", e);
      }
    } else {
      // Si no hay nada guardado, iniciamos con una fila vacía
      addRow({ id: "A", dur: 10, prec: "" });
    }
  }
});
