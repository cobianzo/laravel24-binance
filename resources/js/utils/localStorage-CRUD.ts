// src/utils/storage.ts

// Ejemplo de uso
// saveOptions({ selectedTab: 'tab-favourites' });
// const options = getOptions();
// console.log(options.selectedTab); // 'tab-favourites'


type UserOptions = {
  selectedTab?: string;
  selectedTicker?: string;
  tradePercentages?: { gain: number, loss: number };
  tradeAmount?: number;
  // Aquí puedes añadir más opciones según sea necesario
};

const STORAGE_KEY = 'lar24_user_options';

// Guardar opciones en localStorage
export const saveOptions = (options: UserOptions) => {
  const currentOptions = getOptions();
  const newOptions = { ...currentOptions, ...options };
  localStorage.setItem(STORAGE_KEY, JSON.stringify(newOptions));
};

// Obtener las opciones desde localStorage
export const getOptions = <K extends keyof UserOptions>(key?: K): UserOptions[K] | null => {
  const options = localStorage.getItem(STORAGE_KEY);
  if (!options) return null;
  const optionsObject = JSON.parse(options);
  console.log('object: ', optionsObject);
  if (key === undefined) return optionsObject;
  if (!(key in optionsObject)) return null;
  return optionsObject[key];
};

// Eliminar opciones
export const removeOptions = () => {
  localStorage.removeItem(STORAGE_KEY);
};
