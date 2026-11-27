import * as THREE from 'three';
console.log(THREE);

export function createAmbientLights(){
  const ambience = new THREE.AmbientLight("#ffffff", 0.3);
  return ambience;
}
export function createSun(){
  const sun = new THREE.DirectionalLight("#ffffff", 1);
  sun.position.set(10,10,10);
  sun.castShadow = true;
  return sun;
}