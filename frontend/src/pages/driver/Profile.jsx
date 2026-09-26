import { useState } from "react";
import { useAuth } from "../../context/AuthContext";
import { api } from "../../api";
import {
    useData,
    useAction,
    ActionNotice,
    Notice,
    Panel,
    Field,
    Table,
} from "../../components/DataUI";
export default function Profile() {
    const { user, updateUser } = useAuth();
    const [profile, setProfile] = useState({
        name: user.name,
        email: user.email,
        phone: user.phone || "",
    });
    const empty = { plate_number: "", make: "", model: "", color: "" };
    const [vehicle, setVehicle] = useState(empty);
    const state = useData(`/users/${user.id}/vehicles`),
        action = useAction(state.reload);
    async function save(e) {
        e.preventDefault();
        await action.run(async () => {
            const d = await api(`/users/${user.id}`, {
                method: "PUT",
                body: profile,
            });
            updateUser(d.user);
        }, "Profile saved");
    }
    async function add(e) {
        e.preventDefault();
        if (
            await action.run(
                () =>
                    api(`/users/${user.id}/vehicles`, { method: "POST", body: vehicle }),
                "Vehicle added",
            )
        )
            setVehicle(empty);
    }
    return (
        <>
            <Panel title="Profile">
                <ActionNotice {...action} />
                <form onSubmit={save}>
                    {["name", "email", "phone"].map((k) => (
                        <Field
                            key={k}
                            label={k}
                            name={k}
                            type={k === "email" ? "email" : "text"}
                            required={k !== "phone"}
                            value={profile[k]}
                            onChange={(e) => setProfile({ ...profile, [k]: e.target.value })}
                        />
                    ))}
                    <button className="btn btn-primary" disabled={action.busy}>
                        Save profile
                    </button>
                </form>
            </Panel>
            <Panel title="My vehicles">
                <Notice {...state} />
                <Table
                    rows={state.data || []}
                    columns={["plate_number", "make", "model", "color"].map((k) => ({
                        key: k,
                        label: k.replace("_", " "),
                    }))}
                    actions={(v) => (
                        <button
                            className="btn btn-sm btn-outline-danger"
                            disabled={action.busy}
                            onClick={() => {
                                if (confirm("Delete this unused vehicle?"))
                                    action.run(
                                        () => api("/vehicles/" + v.id, { method: "DELETE" }),
                                        "Vehicle deleted",
                                    );
                            }}
                        >
                            Delete
                        </button>
                    )}
                />
                <form onSubmit={add}>
                    {Object.keys(empty).map((k) => (
                        <Field
                            key={k}
                            label={k.replace("_", " ")}
                            name={k}
                            maxLength={k === "plate_number" ? 20 : k === "color" ? 30 : 50}
                            required={k === "plate_number"}
                            value={vehicle[k]}
                            onChange={(e) => setVehicle({ ...vehicle, [k]: e.target.value })}
                        />
                    ))}
                    <button className="btn btn-success" disabled={action.busy}>
                        Add vehicle
                    </button>
                </form>
            </Panel>
        </>
    );
}
