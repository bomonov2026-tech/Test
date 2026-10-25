# Modelni yig'ib, 5 tomondan studiya render qiladi; .blend va .glb saqlaydi.
# Ishga tushirish: python3 preview.py <out_dir>
import math
import os
import sys

import bpy
from mathutils import Vector

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import car  # noqa: E402

out = os.path.abspath(sys.argv[1])
bpy.ops.wm.read_factory_settings(use_empty=True)
sc = bpy.context.scene
root, wheels, mats = car.build_car(os.path.join(out, "livery.png"))

# Studiya: och kulrang pol, yumshoq yorug'lik
bpy.ops.mesh.primitive_plane_add(size=80)
floor = bpy.context.object
floor.data.materials.append(car.material("Floor", (0.55, 0.56, 0.58), rough=0.3))
w = bpy.data.worlds.new("World")
sc.world = w
w.use_nodes = True
w.node_tree.nodes["Background"].inputs[0].default_value = (0.62, 0.64, 0.67, 1)
w.node_tree.nodes["Background"].inputs[1].default_value = 0.8
for loc, energy, size in (((0, 0, 6), 1500, 6), ((5, 4, 3), 600, 3), ((-5, -4, 3), 500, 3)):
    bpy.ops.object.light_add(type="AREA", location=loc)
    l = bpy.context.object
    l.data.energy, l.data.size = energy, size
    l.rotation_euler = (Vector((0, 0, 0.8)) - Vector(loc)).to_track_quat("-Z", "Y").to_euler()

cam = bpy.data.objects.new("Cam", bpy.data.cameras.new("Cam"))
sc.collection.objects.link(cam)
sc.camera = cam
sc.render.engine = "CYCLES"
sc.cycles.device = "CPU"
sc.cycles.samples = int(os.environ.get("SAMPLES", 64))
sc.cycles.use_denoising = True
sc.render.resolution_x, sc.render.resolution_y = 1280, 720
sc.view_settings.look = "AgX - Medium High Contrast"

target = Vector((0, 0, 0.8))
views = [("1_old", 0, 6, 9.0), ("2_chap", 90, 4, 9.5), ("3_orqa", 180, 6, 9.0),
         ("4_ong", -90, 4, 9.5), ("5_tepa", 90, 89, 11.0), ("6_3x4", 35, 14, 9.0)]
for name, az, el, dist in views:
    a, e = math.radians(az), math.radians(el)
    cam.location = target + dist * Vector((math.cos(e) * math.cos(a), math.cos(e) * math.sin(a), math.sin(e)))
    cam.rotation_euler = (target - cam.location).to_track_quat("-Z", "Y").to_euler()
    if name == "5_tepa":
        cam.rotation_euler.z = math.radians(-90) if False else cam.rotation_euler.z
    cam.data.lens = 50
    sc.render.filepath = os.path.join(out, f"view_{name}.png")
    bpy.ops.render.render(write_still=True)

bpy.ops.wm.save_as_mainfile(filepath=os.path.join(out, "tartibon_car.blend"))
# GLB uchun matnlarni mesh'ga aylantiramiz
for o in [o for o in sc.objects if o.type == "FONT"]:
    with bpy.context.temp_override(selected_editable_objects=[o], active_object=o, object=o):
        bpy.ops.object.convert(target="MESH")
bpy.ops.object.select_all(action="DESELECT")
floor.select_set(False)
for o in sc.objects:
    o.select_set(o.type in {"MESH", "EMPTY"} and o is not floor)
bpy.ops.export_scene.gltf(filepath=os.path.join(out, "tartibon_car.glb"), use_selection=True, export_apply=True)
