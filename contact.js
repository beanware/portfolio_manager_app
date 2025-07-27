import React from 'react';
import { useForm, Controller } from 'react-hook-form';
import emailjs from 'emailjs-com';
import { FaWhatsapp } from 'react-icons/fa';

const ContactForm = () => {
    const { handleSubmit, control, formState: { errors }, getValues } = useForm();
    const [isHovered, setIsHovered] = React.useState(false);

    const handleMouseEnter = () => {
        setIsHovered(true);
    };

    const handleMouseLeave = () => {
        setIsHovered(false);
    };

    const onSubmit = async (data) => {
        const templateParams = {
            from_name: data.name,
            reply_to: data.email,
            message: data.message,
            to_email: 'samuelmikaye2000@gmail.com',
            from_email: 'webuser@example.com',  // Replace with your default "from" email
        };

        try {
            await emailjs.send('service_4xm0lxl', 'template_qzy8v7a', templateParams, process.env.REACT_APP_EMAILJS_USER_ID);
            alert('Message sent successfully!');
        } catch (error) {
            console.error('Failed to send message:', error);
        }
    };

    const handleWhatsAppClick = () => {
        const { name, email, message } = getValues();
        const whatsappMessage = `Name: ${name}%0AEmail: ${email}%0AMessage: ${message}`;
        const whatsappURL = `https://api.whatsapp.com/send?phone=+254 792 716948&text=${whatsappMessage}`;

        window.open(whatsappURL, '_blank');
    };

    return (
        <div style={{
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'center',
            alignItems: 'center',
            height: '100vh'
        }}>
            <form onSubmit={handleSubmit(onSubmit)} style={{
                maxWidth: '600px',
                padding: '100px',
                boxShadow: '0 0 10px rgba(0,0,0,0.1)',
                fontFamily: 'Arial, sans-serif'
            }}>
                <Controller
                    name="name"
                    control={control}
                    defaultValue=""
                    rules={{ required: true }}
                    render={({ field }) => <input {...field} placeholder="Your Name" style={{
                        width: '100%',
                        padding: '10px',
                        border: '1px solid #ccc',
                        borderRadius: '4px',
                        marginBottom: '10px',
                        fontSize: '14px'
                    }} />}
                />
                {errors.name && <span style={{color: 'red'}}>This field is required</span>}
                <Controller
                    name="email"
                    control={control}
                    defaultValue=""
                    rules={{ required: true }}
                    render={({ field }) => <input {...field} type="email" placeholder="Your Email" style={{
                        width: '100%',
                        padding: '10px',
                        border: '1px solid #ccc',
                        borderRadius: '4px',
                        marginBottom: '10px',
                        fontSize: '14px'
                    }} />}
                />
                {errors.email && <span style={{color: 'red'}}>This field is required</span>}
                <Controller
                    name="message"
                    control={control}
                    defaultValue=""
                    rules={{ required: true }}
                    render={({ field }) => <textarea {...field} placeholder="Your Message" style={{
                        width: '100%',
                        padding: '10px',
                        border: '1px solid #ccc',
                        borderRadius: '4px',
                        marginBottom: '10px',
                        resize: 'vertical',
                        fontSize: '14px'
                    }} />}
                />
                {errors.message && <span style={{color: 'red'}}>This field is required</span>}
                <button
                    type="submit" 
                    style={{
                        backgroundColor: isHovered ? 'rgba(255, 255, 255, 0.1)' : '#0d1b2a',
                        color: isHovered ? '#0d1b2a' : 'white',
                        borderRadius: '4px',
                        borderStyle: 'solid',
                        padding: '10px 20px',
                        border: 'none',
                        borderRadius: '4px',
                        cursor: 'pointer',
                        fontSize: '16px',
                        transition: 'all 0.2s ease-in-out',
                        ...(isHovered && {
                            backdropFilter: 'blur(10px)'
                        })
                    }}
                    onMouseEnter={handleMouseEnter}
                    onMouseLeave={handleMouseLeave}
                >
                    Send Message
                </button>
                <div style={{ marginTop: '20px', textAlign: 'center' }}>
                    <FaWhatsapp
                        size={32}
                        style={{ cursor: 'pointer', color: '#25D366' }}
                        onClick={handleWhatsAppClick}
                    />
                </div>
            </form>
        </div>
    );
};

export default ContactForm;
