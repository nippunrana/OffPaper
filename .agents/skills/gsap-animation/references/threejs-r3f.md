# 3D Animations with React Three Fiber (R3F) & Scroll-Mapping

Integrating 3D models into web layouts requires combining WebGL rendering with the component lifecycle. This document outlines the technical workflow for managing Three.js canvases, loading assets, and binding 3D animations (like model rotations) to scroll progress.

---

## Technical Environment Setup

### 1. Canvas Dimensions and CSS Resets
The `<Canvas>` component from React Three Fiber (R3F) inherits its dimensions from its parent container. If the parent container has `0px` height or width, the canvas will fail to render.
Ensure the root and parent containers are explicitly sized in your global stylesheet (`index.css` or `style.css`):

```css
html, body, #root {
  width: 100%;
  height: 100%;
  margin: 0;
  padding: 0;
  overflow-x: hidden;
  background-color: #000; /* Standard default dark background */
}
```

### 2. Pointer Events and Text Interactivity
3D overlays can block standard HTML link clicks and text selections if they are positioned over the text. 
- Use the CSS property `pointer-events: none` on the overlay wrapper container.
- Re-enable pointers on interactive elements inside the overlay using `pointer-events: auto`.

---

## 3D Scene Setup Standards

To achieve a professional, polished "Apple-style" product showcase (e.g., a 3D laptop page), follow these baseline properties:

### 1. Camera Configurations
- **Field of View (FOV)**: Set to `20` to reduce perspective distortion and keep product angles flat and professional.
- **Position**: Set to `[0, -2, 120]` on the X, Y, and Z axes to keep the model centered and properly framed inside the viewport.

### 2. Global Lighting and Environment
Avoid manual placing of individual lights. Instead, use Drei's `<Environment>` helper to apply a High Dynamic Range Image (HDRI) file. This yields realistic studio-grade reflections and ambient light:

```javascript
import { Environment } from "@react-three/drei";

<Environment files="/studio_lighting.hdr" />
```

---

## Model Manipulation and Traversal

A loaded 3D model (usually loaded via Drei's `useGLTF` hook) consists of a hierarchical tree of groups and meshes. To animate specific parts of a model (like a laptop lid screen), you must traverse the model object to target the specific mesh.

### 1. Group Wrapping
Always wrap the primitive model component in a `<group>` tag. This allows you to apply global transforms (global position and rotation) to the group without breaking the coordinates of the internal meshes.

### 2. Mesh Traversal & Selection
Traverse the model to target specific components by name:

```javascript
const { scene } = useGLTF("/macbook.gltf");

// Locate the screen mesh within the scene
let screenMesh;
scene.traverse((child) => {
  if (child.isMesh && child.name === "screen") {
    screenMesh = child;
  }
});
```

### 3. Screen Textures and EmissiveIntensity
When applying a texture to animate a screen on a model:
- Load the image using Drei's `useTexture`.
- Assign it to the mesh material map: `screenMesh.material.map = texture`.
- **Critical**: Set `emissiveIntensity: 0` (or `emissive` color to black) on the screen material. If environment lights are bright, they will wash out the texture if emissive properties are enabled.

---

## Scroll-Driven 3D Animations (Scroll-Mapping)

Professional scroll-driven 3D animations map a normalized scroll offset (from `0` to `1`) to 3D coordinate transformations. 

### 1. The useScroll Hook and useFrame Render Loop
Drei's `useScroll` hook yields real-time normalized scroll tracking values (`data.offset`), which are then read inside the `useFrame` render loop. The `useFrame` hook acts as the heartbeat of the scene, executing on every frame of the system's refresh rate for smooth 60fps+ transitions.

### 2. Mathematical Precision: Converting Degrees to Radians
Three.js uses **radians** for rotation, not degrees. Use `THREE.MathUtils.degToRad()` to map scroll offsets to rotation angles.

### 3. Code Pattern: MacBook Pro Scrolling Lid
Here is the standard implementation for rotating a laptop lid from a closed state (180 degrees) to an open state (90 degrees) based on scroll progress:

```javascript
import { useScroll } from "@react-three/drei";
import { useFrame } from "@react-three/fiber";
import * as THREE from "three";

const LaptopModel = () => {
  const data = useScroll(); // Returns normalized offset (0 to 1)
  const groupRef = useRef();

  useFrame((state, delta) => {
    const offset = data.offset; // Normalized scroll value (0 to 1)
    
    // Find the screen mesh (rely on a ref or traverse once at mount)
    const screenMesh = groupRef.current.getObjectByName("screen");
    
    if (screenMesh) {
      // 180 degrees (closed) down to 90 degrees (open)
      const startRotation = THREE.MathUtils.degToRad(180);
      const rotationRange = THREE.MathUtils.degToRad(90);
      
      // Calculate rotation.x based on the scroll percentage
      screenMesh.rotation.x = startRotation - (offset * rotationRange);
    }
  });

  return (
    <group ref={groupRef}>
      <primitive object={scene} />
    </group>
  );
};
```

---

## Key Best Practices

- ✅ **Use Group Anchors**: Rotate and position the model globally by animating the parent `<group>`, and use child mesh transforms for sub-elements.
- ✅ **Convert to Radians**: Always use `THREE.MathUtils.degToRad()` when mapping degree rotations.
- ✅ **Reset CSS Pointer Events**: Apply `pointer-events: none` on overlays that sit on top of 3D Canvas elements.
- ✅ **Emissive Textures**: Ensure `emissiveIntensity: 0` is set on textured meshes (like computer/phone screens) to prevent light washing.

## Do Not

- ❌ **Use Hardcoded Pixel Values**: Do not animate 3D properties using fixed scroll pixels; use the normalized `0` to `1` offset from `useScroll`.
- ❌ **Perform Heavy Traversal in useFrame**: Do not call `scene.traverse()` inside the `useFrame` loop. Perform traversal once at mount or retrieve meshes using `groupRef.current.getObjectByName("meshName")` to prevent performance jank.
- ❌ **Forget Parent Container Heights**: Do not mount a `<Canvas>` inside a parent div that lacks styling. The canvas width/height will collapse to 0.
