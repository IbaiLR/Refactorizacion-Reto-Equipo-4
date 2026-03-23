<script setup lang="ts">
import Toast from "@/components/Notification/Toast.vue";
import type { Curso } from "@/interfaces/Curso";
import type { TutorEgibide } from "@/interfaces/TutorEgibide";
import { useAlumnosStore } from "@/stores/alumnos";
import { useCiclosStore } from "@/stores/ciclos";
import { onMounted, ref, watch } from "vue";

const alumnoStore = useAlumnosStore();
const cicloStore = useCiclosStore();

// --- Campos del formulario ---
const nombre = ref<string>("");
const apellidos = ref<string>("");
const matricula = ref<string>("");
const dni = ref<string>("");
const telefono = ref<number>(0);
const poblacion = ref<string>("");

const ciclo = ref<number>(0);
const curso = ref<number>(0);
const tutor = ref<number>(0);

const listaCiclos = ref<any[]>([]);
const listaCursos = ref<Curso[]>([]);
const listaTutores = ref<TutorEgibide[]>([]);

// --- Watch para actualizar tutores según ciclo ---
watch(ciclo, async (newVal) => {
  if (!newVal) {
    listaCursos.value = [];
    curso.value = 0;
    listaTutores.value = [];
    tutor.value = 0;
    return;
  }

  await cicloStore.fetchTutoresByCiclos(newVal);
  listaTutores.value = cicloStore.tutores;
  tutor.value = 0;
});

// --- Fetch inicial de ciclos ---
onMounted(async () => {
  await cicloStore.fetchCiclos();
  listaCiclos.value = cicloStore.ciclos;
});

// --- Función agregar alumno ---
async function agregarAlumno() {
  const ok = await alumnoStore.createAlumno(
    nombre.value,
    apellidos.value,
    matricula.value,
    dni.value,
    telefono.value,
    poblacion.value,
    ciclo.value,
    tutor.value
  );

  if (ok) {
    resetForms();
  }
}

// --- Reset formulario ---
function resetForms() {
  nombre.value = "";
  apellidos.value = "";
  matricula.value = "";
  dni.value = "";
  telefono.value = 0;
  poblacion.value = "";
  ciclo.value = 0;
  curso.value = 0;
  tutor.value = 0;
}
</script>

<template>
  <div class="container my-4">
    <div class="row justify-content-center">
      <div class="col-lg-10 col-xl-9">
        <div class="card shadow-sm">
          <div class="card-body">
            <!-- HEADER -->
            <div class="d-flex align-items-center mb-3">
              <i class="bi bi-person-plus-fill fs-3 text-primary me-2"></i>
              <h3 class="mb-0">Nuevo alumno</h3>
            </div>

            <p class="text-muted mb-4">
              Introduce los datos del alumno para registrarlo en el sistema
            </p>

            <Toast
              v-if="alumnoStore.message"
              :message="alumnoStore.message"
              :messageType="alumnoStore.messageType"
            />

            <form @submit.prevent="agregarAlumno">
              <div class="row g-3">
                <!-- DATOS PERSONALES -->
                <div class="col-md-6">
                  <label class="form-label">Nombre</label>
                  <input type="text" class="form-control" placeholder="Markel" v-model="nombre" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Apellidos</label>
                  <input type="text" class="form-control" placeholder="Goikoetxea" v-model="apellidos" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Matrícula</label>
                  <input type="text" class="form-control" placeholder="00000" v-model="matricula" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label">DNI</label>
                  <input type="text" class="form-control" placeholder="12345678A" v-model="dni" required />
                </div>

                <div class="col-md-6">
                  <label class="form-label">Teléfono</label>
                  <input type="tel" class="form-control" placeholder="644202601" v-model.number="telefono" required />
                </div>

                <div class="col-12">
                  <label class="form-label">Población</label>
                  <input type="text" class="form-control" placeholder="Vitoria-Gasteiz, Agurain..." v-model="poblacion" required />
                </div>

                <hr class="my-4" />

                <!-- DATOS ACADÉMICOS -->
                <div class="col-md-6">
                  <label class="form-label">Ciclo</label>
                  <select class="form-select" v-model.number="ciclo" required>
                    <option :value="0" disabled>-- Selecciona un ciclo --</option>
                    <option v-for="c in listaCiclos" :key="c.id" :value="c.id">
                      {{ c.nombre }}
                    </option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Tutor</label>
                  <select class="form-select" v-model.number="tutor" required>
                    <option :value="0" disabled>-- Selecciona un tutor --</option>
                    <option v-for="t in listaTutores" :key="t.id" :value="t.id">
                      {{ t.nombre }} {{ t.apellidos }}
                    </option>
                  </select>
                </div>

                <!-- BOTÓN -->
                <div class="col-12 text-center mt-4">
                  <button type="submit" class="btn btn-primary px-5">
                    <i class="bi bi-check-circle me-1"></i>
                    Agregar alumno
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.card {
  border-radius: 0.75rem;
}
</style>
